#!/usr/bin/env python3
"""
Robust paginated Invoice pull using the B2C app credentials (fresh token).
Bulk SELECT * Invoice 500s on this large table, so page in small chunks with
retries + page-shrink on error. Writes Invoice.json + updates manifest in the
target backup dir (arg 1, else latest qbo-backups/*).
"""
import os, sys, json, time, base64, hashlib, urllib.parse, urllib.request, urllib.error

HERE = os.path.dirname(os.path.abspath(__file__))
ENV = os.path.join(HERE, ".env")
API = "https://quickbooks.api.intuit.com/v3/company"
TOKEN_URL = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer"
ENTITY = "Invoice"
PAGE = 100


def load_env():
    env = {}
    for line in open(ENV):
        s = line.strip()
        if s and not s.startswith("#") and "=" in s:
            k, v = s.split("=", 1); env[k.strip()] = v.strip().strip('"').strip("'")
    return env


def refresh(env):
    auth = base64.b64encode(f"{env['QBO_B2C_CLIENT_ID']}:{env['QBO_B2C_CLIENT_SECRET']}".encode()).decode()
    req = urllib.request.Request(TOKEN_URL,
        data=urllib.parse.urlencode({"grant_type": "refresh_token",
                                     "refresh_token": env["QBO_B2C_REFRESH_TOKEN"]}).encode(),
        headers={"Authorization": f"Basic {auth}", "Accept": "application/json",
                 "Content-Type": "application/x-www-form-urlencoded"}, method="POST")
    return json.loads(urllib.request.urlopen(req, timeout=60).read())["access_token"]


def fetch_page(realm, token, start, page):
    q = f"SELECT * FROM {ENTITY} ORDERBY Id STARTPOSITION {start} MAXRESULTS {page}"
    url = f"{API}/{realm}/query?query={urllib.parse.quote(q)}&minorversion=70"
    req = urllib.request.Request(url, headers={"Authorization": f"Bearer {token}", "Accept": "application/json"})
    with urllib.request.urlopen(req, timeout=120) as r:
        return json.loads(r.read().decode()).get("QueryResponse", {}).get(ENTITY, [])


def main():
    env = load_env()
    outdir = sys.argv[1] if len(sys.argv) > 1 else \
        os.path.join(HERE, "qbo-backups", sorted(os.listdir(os.path.join(HERE, "qbo-backups")))[-1])
    realm = env["QBO_B2C_REALM_ID"]
    token = refresh(env)

    jsonl = os.path.join(outdir, f"{ENTITY}.partial.jsonl")
    prog = os.path.join(outdir, f"{ENTITY}.progress")
    done_marker = os.path.join(outdir, f"{ENTITY}.done")

    # resume from checkpoint
    start = int(open(prog).read().strip()) if os.path.exists(prog) else 1
    page = PAGE
    print(f"Invoice pull -> {outdir}  (realm {realm})  resume start={start}")

    if os.path.exists(done_marker):
        print("Already complete."); return

    with open(jsonl, "a") as out:
        while True:
            for attempt in range(6):
                try:
                    batch = fetch_page(realm, token, start, page)
                    break
                except urllib.error.HTTPError as e:
                    wait = 2 ** attempt
                    if e.code in (401,):
                        token = refresh(env)
                    elif e.code in (500, 503, 429):
                        page = max(25, page // 2)
                    print(f"  start={start} HTTP {e.code}, retry {attempt+1} (page->{page}) in {wait}s")
                    time.sleep(wait)
                except Exception as ex:
                    print(f"  start={start} {ex}, retry {attempt+1}"); time.sleep(2 ** attempt)
            else:
                print(f"  ABORT at start={start}"); sys.exit(2)
            if not batch:
                break
            for rec in batch:
                out.write(json.dumps(rec) + "\n")
            out.flush(); os.fsync(out.fileno())
            start += len(batch)
            open(prog, "w").write(str(start))          # checkpoint next start
            print(f"  +{len(batch)} -> next start={start}", flush=True)
            if len(batch) < page:
                break
            time.sleep(0.1)

    # assemble final Invoice.json from the jsonl (dedupe by Id)
    seen, rows = set(), []
    for line in open(jsonl):
        r = json.loads(line)
        if r.get("Id") not in seen:
            seen.add(r.get("Id")); rows.append(r)
    blob = json.dumps(rows, indent=2, sort_keys=True).encode()
    open(os.path.join(outdir, f"{ENTITY}.json"), "wb").write(blob)
    mpath = os.path.join(outdir, "manifest.json")
    man = json.load(open(mpath)) if os.path.exists(mpath) else {"entities": {}}
    man.setdefault("entities", {})[ENTITY] = {"count": len(rows), "sha256": hashlib.sha256(blob).hexdigest()}
    json.dump(man, open(mpath, "w"), indent=2)
    open(done_marker, "w").write(str(len(rows)))
    print(f"\nDone. {len(rows)} invoices written + manifest updated.")


if __name__ == "__main__":
    main()

#!/usr/bin/env python3
"""
Thin cPanel UAPI/API2 client for the Ah Ho Vodien account.

Shell access is disabled on this account, so file + cron management all go
through the cPanel API. Credentials come from .env (VODIEN_CPANEL_*), never
from the command line — the password must not land in shell history or ps.

Usage:
  python3 tools/cpanel.py ls <dir>
  python3 tools/cpanel.py cat <dir> <file>
  python3 tools/cpanel.py put <local> <remote-dir> [remote-name]
  python3 tools/cpanel.py rm <abs-path>
  python3 tools/cpanel.py cron-list
  python3 tools/cpanel.py cron-add <min> <hour> <day> <month> <weekday> <command>
  python3 tools/cpanel.py cron-del <linekey>
  python3 tools/cpanel.py sh <command>     # run via a 1-shot cron, print output
"""
import os, sys, json, time, base64, urllib.parse, urllib.request, mimetypes, uuid

HERE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))


def env():
    e = {}
    with open(os.path.join(HERE, '.env')) as f:
        for line in f:
            line = line.strip()
            if line and not line.startswith('#') and '=' in line:
                k, v = line.split('=', 1)
                e[k.strip()] = v.strip().strip('"').strip("'")
    return e


E = env()
USER = E['VODIEN_CPANEL_USER']
HOST = E.get('VODIEN_CPANEL_URL', 'https://sh00017.vodien.com:2083').rstrip('/')
if not HOST.startswith('http'):
    HOST = 'https://' + HOST
AUTH = 'Basic ' + base64.b64encode(f"{USER}:{E['VODIEN_CPANEL_PASS']}".encode()).decode()
HOME = f'/home2/{USER}'


def _get(url):
    req = urllib.request.Request(url, headers={'Authorization': AUTH})
    with urllib.request.urlopen(req, timeout=90) as r:
        return json.loads(r.read().decode('utf-8', 'replace'), strict=False)


def uapi(module, func, **params):
    return _get(f"{HOST}/execute/{module}/{func}?" + urllib.parse.urlencode(params))


def api2(module, func, **params):
    p = {'cpanel_jsonapi_apiversion': 2, 'cpanel_jsonapi_module': module,
         'cpanel_jsonapi_func': func, **params}
    return _get(f"{HOST}/json-api/cpanel?" + urllib.parse.urlencode(p))


def put(local, remote_dir, name=None):
    """Multipart upload with overwrite."""
    name = name or os.path.basename(local)
    boundary = '----ahho' + uuid.uuid4().hex
    with open(local, 'rb') as f:
        payload = f.read()
    ctype = mimetypes.guess_type(name)[0] or 'application/octet-stream'
    body = b''
    for k, v in (('dir', remote_dir), ('overwrite', '1')):
        body += (f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n').encode()
    body += (f'--{boundary}\r\nContent-Disposition: form-data; name="file-1"; '
             f'filename="{name}"\r\nContent-Type: {ctype}\r\n\r\n').encode()
    body += payload + f'\r\n--{boundary}--\r\n'.encode()
    req = urllib.request.Request(f'{HOST}/execute/Fileman/upload_files', data=body,
                                 headers={'Authorization': AUTH,
                                          'Content-Type': f'multipart/form-data; boundary={boundary}'})
    with urllib.request.urlopen(req, timeout=180) as r:
        return json.loads(r.read().decode('utf-8', 'replace'), strict=False)


def cat(d, f):
    r = uapi('Fileman', 'get_file_content', dir=d, file=f)
    if not r.get('data'):
        return None
    return r['data']['content']


def sh(command, timeout=180):
    """No shell access on this account: run a command via a 1-shot cron and
    read its output back. Slow (up to ~70s) but it is the only way."""
    out = f'{HOME}/.sh-out-{uuid.uuid4().hex[:8]}.txt'
    # The shell creates `out` the instant the redirect is set up, so polling for
    # existence alone reads a half-written file. Write to a temp and rename only
    # once the command has finished — rename is atomic, so seeing `out` means done.
    full = f'({command}) > {out}.part 2>&1; mv {out}.part {out}'
    r = api2('Cron', 'add_line', minute='*', hour='*', day='*', month='*',
             weekday='*', command=full)
    key = r['cpanelresult']['data'][0]['linekey']
    try:
        deadline = time.time() + timeout
        while time.time() < deadline:
            time.sleep(10)
            c = cat('/', os.path.basename(out))
            if c is not None:
                return c
        return '[timeout waiting for cron]'
    finally:
        api2('Cron', 'remove_line', linekey=key, line=1, commandnumber=2)
        api2('Fileman', 'fileop', op='unlink', sourcefiles=out, doubledecode=0)


def main():
    a = sys.argv[1:]
    if not a:
        print(__doc__); return 1
    c = a[0]
    if c == 'ls':
        r = uapi('Fileman', 'list_files', dir=a[1], types='file,dir')
        for f in r.get('data') or []:
            print(f"{f.get('nicemode')}  {f.get('size'):>9}  {f.get('file')}")
    elif c == 'cat':
        print(cat(a[1], a[2]))
    elif c == 'put':
        print(json.dumps(put(a[1], a[2], a[3] if len(a) > 3 else None).get('data'), indent=1))
    elif c == 'rm':
        print(api2('Fileman', 'fileop', op='unlink', sourcefiles=a[1], doubledecode=0)['cpanelresult']['data'])
    elif c == 'cron-list':
        for j in api2('Cron', 'listcron')['cpanelresult']['data']:
            if 'command' in j:
                print(f"[{j['linekey']}] {j['minute']} {j['hour']} {j['day']} {j['month']} {j['weekday']}  {j['command']}")
    elif c == 'cron-add':
        print(api2('Cron', 'add_line', minute=a[1], hour=a[2], day=a[3], month=a[4],
                   weekday=a[5], command=' '.join(a[6:]))['cpanelresult']['data'])
    elif c == 'cron-del':
        print(api2('Cron', 'remove_line', linekey=a[1], line=1, commandnumber=2)['cpanelresult']['data'])
    elif c == 'sh':
        print(sh(' '.join(a[1:])))
    else:
        print(__doc__); return 1
    return 0


if __name__ == '__main__':
    sys.exit(main())

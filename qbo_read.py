"""Read-only access to the Ah Ho Fruit QuickBooks Online company."""

import importlib.util
import os
import re
import urllib.parse


HERE = os.path.dirname(os.path.abspath(__file__))
_spec = importlib.util.spec_from_file_location(
    "ahho_sync", os.path.join(HERE, "b2c-qbo-salesreceipt-sync.py")
)
ahho_sync = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(ahho_sync)


def _server_env():
    """The sync's .env on Vodien, which is the AUTHORITATIVE token store.

    Returns (cpanel_module, parsed_env) or (None, None) if unreachable.
    """
    try:
        spec = importlib.util.spec_from_file_location(
            "ahho_cpanel", os.path.join(HERE, "tools", "cpanel.py")
        )
        cp = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(cp)
        raw = cp.cat("/ahho-qbo", ".env")
        if not raw:
            return None, None
        parsed = {}
        for line in raw.splitlines():
            line = line.strip()
            if line and not line.startswith("#") and "=" in line:
                k, v = line.split("=", 1)
                parsed[k.strip()] = v.strip().strip('"').strip("'")
        return cp, parsed
    except Exception:
        return None, None


class ReadOnlyQBO:
    """Small read-only facade over the existing QBO OAuth and query client.

    Token custody note: Intuit ROTATES the refresh token on every refresh, and
    the live sync runs on Vodien. If this client used the local .env copy it
    would either fail with invalid_grant or, worse, rotate the token out from
    under the server and break the nightly/monthly sync.

    So we read the current token from the server before connecting, and write
    any rotation straight back. The proper fix is a SEPARATE Intuit connection
    for reporting (needs a one-off OAuth by Lex) — see server/README.md.
    """

    def __init__(self, env=None, env_path=None):
        # This injection seam can support a future multi-realm layer.
        if env is None:
            resolved_path = (
                env_path
                or os.environ.get("AHHO_ENV_PATH")
                or os.path.join(HERE, ".env")
            )
            env = ahho_sync.load_env(resolved_path)

        self.__cp = None
        if os.environ.get("AHHO_LOCAL_TOKEN") != "1":
            cp, server = _server_env()
            token = (server or {}).get("QBO_B2C_REFRESH_TOKEN")
            if token:
                env = dict(env, QBO_B2C_REFRESH_TOKEN=token)
                self.__cp = cp

        self.__qbo = ahho_sync.QBO(env)
        self.__initial_refresh = self.__qbo.refresh

    def _sync_token_back(self):
        """If Intuit rotated our refresh token, hand it back to the server."""
        if not self.__cp or self.__qbo.refresh == self.__initial_refresh:
            return
        try:
            raw = self.__cp.cat("/ahho-qbo", ".env")
            new = re.sub(
                r"^QBO_B2C_REFRESH_TOKEN=.*$",
                "QBO_B2C_REFRESH_TOKEN=" + self.__qbo.refresh,
                raw,
                flags=re.MULTILINE,
            )
            import tempfile
            with tempfile.NamedTemporaryFile("w", suffix=".env", delete=False) as f:
                f.write(new)
                tmp = f.name
            self.__cp.put(tmp, "/ahho-qbo", ".env")
            os.unlink(tmp)
            self.__initial_refresh = self.__qbo.refresh
        except Exception:
            # Never let bookkeeping break a read; the server re-auths on failure.
            pass

    def run_query(self, query):
        """Run a QBO SELECT query and return its QueryResponse dictionary."""
        if not isinstance(query, str) or re.match(
            r"^select\b", query.strip(), re.IGNORECASE
        ) is None:
            raise ValueError("read-only: only SELECT queries allowed")
        result = self.__qbo.query(query)
        self._sync_token_back()
        return result

    def report(self, name, params=None):
        """Fetch a report from QBO's read-only Reports API."""
        url = (
            f"{ahho_sync.QBO_BASE}/{self.__qbo.realm}/reports/{name}"
            "?minorversion=70"
        )
        if params:
            url += "&" + urllib.parse.urlencode(params)
        _, payload = ahho_sync._req(url, "GET", self.__qbo._h())
        self._sync_token_back()
        return payload

    def list_unpaid_invoices(self, limit=100):
        """Return a compact list of invoices which still have a balance."""
        query = (
            "select Id,DocNumber,TxnDate,DueDate,CustomerRef,TotalAmt,Balance "
            "from Invoice where Balance > '0' orderby TxnDate desc "
            f"maxresults {int(limit)}"
        )
        response = self.run_query(query)
        return [
            {
                "doc_number": invoice.get("DocNumber"),
                "customer": invoice.get("CustomerRef", {}).get("name"),
                "txn_date": invoice.get("TxnDate"),
                "due_date": invoice.get("DueDate"),
                "total": invoice.get("TotalAmt"),
                "balance": invoice.get("Balance"),
            }
            for invoice in response.get("Invoice", [])
        ]

    def invoice_detail(self, doc_number):
        """Return the full QBO record for one invoice DocNumber, if found."""
        escaped_doc_number = str(doc_number).replace("'", "\\'")
        response = self.run_query(
            f"select * from Invoice where DocNumber = '{escaped_doc_number}'"
        )
        invoices = response.get("Invoice", [])
        return invoices[0] if invoices else None

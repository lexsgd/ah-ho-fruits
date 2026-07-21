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


class ReadOnlyQBO:
    """Small read-only facade over the existing QBO OAuth and query client."""

    def __init__(self, env=None, env_path=None):
        # This injection seam can support a future multi-realm layer.
        if env is None:
            resolved_path = (
                env_path
                or os.environ.get("AHHO_ENV_PATH")
                or os.path.join(HERE, ".env")
            )
            env = ahho_sync.load_env(resolved_path)
        self.__qbo = ahho_sync.QBO(env)

    def run_query(self, query):
        """Run a QBO SELECT query and return its QueryResponse dictionary."""
        if not isinstance(query, str) or re.match(
            r"^select\b", query.strip(), re.IGNORECASE
        ) is None:
            raise ValueError("read-only: only SELECT queries allowed")
        return self.__qbo.query(query)

    def report(self, name, params=None):
        """Fetch a report from QBO's read-only Reports API."""
        url = (
            f"{ahho_sync.QBO_BASE}/{self.__qbo.realm}/reports/{name}"
            "?minorversion=70"
        )
        if params:
            url += "&" + urllib.parse.urlencode(params)
        _, payload = ahho_sync._req(url, "GET", self.__qbo._h())
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

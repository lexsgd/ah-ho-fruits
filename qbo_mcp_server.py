# READ-ONLY invariant: this server exposes only QBO reads; QBO.post is never wired.

import json

from mcp.server.fastmcp import FastMCP

from qbo_read import ReadOnlyQBO


mcp = FastMCP("Ah Ho Fruit QuickBooks Online (read-only)")
_qbo: ReadOnlyQBO | None = None


def _get_qbo() -> ReadOnlyQBO:
    global _qbo
    if _qbo is None:
        _qbo = ReadOnlyQBO()
    return _qbo


@mcp.tool()
def run_query(query: str) -> str:
    """Run an arbitrary read-only QBO SELECT query and return its QueryResponse."""
    return json.dumps(_get_qbo().run_query(query))


@mcp.tool()
def report(name: str, params: dict | None = None) -> str:
    """Fetch a QBO report, such as ProfitAndLoss with start_date and end_date params."""
    return json.dumps(_get_qbo().report(name, params))


@mcp.tool()
def list_unpaid_invoices(limit: int = 100) -> str:
    """List open invoices with a balance: money customers still owe Ah Ho Fruit."""
    return json.dumps(_get_qbo().list_unpaid_invoices(limit))


@mcp.tool()
def invoice_detail(doc_number: str) -> str:
    """Return the full detail of one QBO invoice identified by its DocNumber."""
    return json.dumps(_get_qbo().invoice_detail(doc_number))


if __name__ == "__main__":
    mcp.run()

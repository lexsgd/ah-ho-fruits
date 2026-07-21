"""Offline tests for the read-only QBO facade."""

import pathlib
import urllib.parse
import unittest
from unittest import mock

import qbo_read


class ReadOnlyQBOTests(unittest.TestCase):
    def setUp(self):
        self.env = {
            "QBO_B2C_REALM_ID": "test-realm",
            "QBO_B2C_CLIENT_ID": "test-client",
            "QBO_B2C_CLIENT_SECRET": "test-secret",
            "QBO_B2C_REFRESH_TOKEN": "test-refresh",
        }

    @staticmethod
    def token_response():
        return 200, {"access_token": "offline-access-token"}

    def test_run_query_rejects_non_select_and_passes_select_through(self):
        client = qbo_read.ReadOnlyQBO(env=self.env)
        with self.assertRaisesRegex(
            ValueError, "read-only: only SELECT queries allowed"
        ):
            client.run_query("update Invoice set Balance = '0'")
        with self.assertRaises(ValueError):
            client.run_query("selection from Invoice")

        requested_queries = []

        def fake_req(url, method="GET", headers=None, data=None):
            if url == qbo_read.ahho_sync.TOKEN_URL:
                return self.token_response()
            parsed = urllib.parse.urlparse(url)
            requested_queries.append(urllib.parse.parse_qs(parsed.query)["query"][0])
            return 200, {"QueryResponse": {"Invoice": [{"Id": "1"}]}}

        query = "select Id from Invoice maxresults 1"
        with mock.patch.object(qbo_read.ahho_sync, "_req", side_effect=fake_req):
            result = client.run_query(query)

        self.assertEqual(requested_queries, [query])
        self.assertEqual(result, {"Invoice": [{"Id": "1"}]})

    def test_report_builds_reports_url_and_urlencodes_dates(self):
        client = qbo_read.ReadOnlyQBO(env=self.env)
        requested_urls = []

        def fake_req(url, method="GET", headers=None, data=None):
            if url == qbo_read.ahho_sync.TOKEN_URL:
                return self.token_response()
            requested_urls.append(url)
            self.assertEqual(method, "GET")
            return 200, {"Header": {"ReportName": "ProfitAndLoss"}}

        params = {"start_date": "2026-06-01", "end_date": "2026-06-30"}
        with mock.patch.object(qbo_read.ahho_sync, "_req", side_effect=fake_req):
            result = client.report("ProfitAndLoss", params)

        self.assertEqual(result["Header"]["ReportName"], "ProfitAndLoss")
        self.assertEqual(len(requested_urls), 1)
        url = requested_urls[0]
        self.assertIn("/reports/ProfitAndLoss", url)
        parsed_params = urllib.parse.parse_qs(urllib.parse.urlparse(url).query)
        self.assertEqual(parsed_params["minorversion"], ["70"])
        self.assertEqual(parsed_params["start_date"], ["2026-06-01"])
        self.assertEqual(parsed_params["end_date"], ["2026-06-30"])

    def test_list_unpaid_invoices_queries_balance_and_compacts_results(self):
        client = qbo_read.ReadOnlyQBO(env=self.env)
        requested_queries = []
        invoices = [
            {
                "Id": "11",
                "DocNumber": "INV-101",
                "TxnDate": "2026-06-14",
                "DueDate": "2026-07-14",
                "CustomerRef": {"value": "7", "name": "Fresh Market"},
                "TotalAmt": 250.0,
                "Balance": 100.0,
            },
            {
                "Id": "12",
                "DocNumber": "INV-102",
                "TxnDate": "2026-06-20",
                "DueDate": "2026-07-20",
                "CustomerRef": {"value": "8", "name": "Orchard Cafe"},
                "TotalAmt": 80.5,
                "Balance": 80.5,
            },
        ]

        def fake_req(url, method="GET", headers=None, data=None):
            if url == qbo_read.ahho_sync.TOKEN_URL:
                return self.token_response()
            parsed = urllib.parse.urlparse(url)
            requested_queries.append(urllib.parse.parse_qs(parsed.query)["query"][0])
            return 200, {"QueryResponse": {"Invoice": invoices}}

        with mock.patch.object(qbo_read.ahho_sync, "_req", side_effect=fake_req):
            result = client.list_unpaid_invoices(limit=2)

        self.assertIn("Balance > '0'", requested_queries[0])
        self.assertEqual(
            result,
            [
                {
                    "doc_number": "INV-101",
                    "customer": "Fresh Market",
                    "txn_date": "2026-06-14",
                    "due_date": "2026-07-14",
                    "total": 250.0,
                    "balance": 100.0,
                },
                {
                    "doc_number": "INV-102",
                    "customer": "Orchard Cafe",
                    "txn_date": "2026-06-20",
                    "due_date": "2026-07-20",
                    "total": 80.5,
                    "balance": 80.5,
                },
            ],
        )

    def test_read_only_invariant(self):
        client = qbo_read.ReadOnlyQBO(env=self.env)
        self.assertFalse(hasattr(client, "post"))
        source = pathlib.Path(qbo_read.__file__).read_text(encoding="utf-8")
        self.assertNotIn(".post(", source)


if __name__ == "__main__":
    unittest.main()

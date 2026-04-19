<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

final class MailerFixtureContent
{
    private const PDF_BASE64 = 'JVBERi0xLjQKJeLjz9MKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgL01lZGlhQm94IFswIDAgNDAwIDIwMF0gPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvUmVzb3VyY2VzIDw8IC9Gb250IDw8IC9GMSA8PCAvVHlwZSAvRm9udCAvU3VidHlwZSAvVHlwZTEgL0Jhc2VGb250IC9IZWx2ZXRpY2EgPj4gPj4gPj4gL0NvbnRlbnRzIDQgMCBSID4+CmVuZG9iago0IDAgb2JqCjw8IC9MZW5ndGggNTMgPj4Kc3RyZWFtCkJUIC9GMSAxOCBUZiA0MCAxMjAgVGQgKEFEUCBmaXh0dXJlIGF0dGFjaG1lbnQpIFRqIEVUCmVuZHN0cmVhbQplbmRvYmoKeHJlZgowIDUKMDAwMDAwMDAwMCA2NTUzNSBmIAowMDAwMDAwMDE1IDAwMDAwIG4gCjAwMDAwMDAwNjQgMDAwMDAgbiAKMDAwMDAwMDE0NSAwMDAwMCBuIAowMDAwMDAwMjk2IDAwMDAwIG4gCnRyYWlsZXIKPDwgL1NpemUgNSAvUm9vdCAxIDAgUiA+PgpzdGFydHhyZWYKMzk5CiUlRU9GCg==';

    public static function tableHtml(): string
    {
        return <<<'HTML'
            <!DOCTYPE html>
            <html lang="en">
            <body style="font-family: Arial, sans-serif; color: #222; margin: 0; padding: 24px; background: #f5f5f7;">
              <h2 style="margin: 0 0 12px 0;">Weekly traffic report</h2>
              <p style="margin: 0 0 16px 0; color: #555;">Aggregated metrics for the last 7 days.</p>
              <table cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 520px; background: #fff; border: 1px solid #e2e2e4;">
                <thead>
                  <tr style="background: #111; color: #fff;">
                    <th align="left">Metric</th>
                    <th align="right">Value</th>
                    <th align="right">Δ vs prev.</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td>Requests</td><td align="right">1 240</td><td align="right" style="color:#22863a;">+12%</td></tr>
                  <tr style="background:#fafafa;"><td>Errors</td><td align="right">12</td><td align="right" style="color:#b31d28;">+3%</td></tr>
                  <tr><td>Avg response</td><td align="right">84 ms</td><td align="right" style="color:#22863a;">-8%</td></tr>
                  <tr style="background:#fafafa;"><td>P95 response</td><td align="right">220 ms</td><td align="right" style="color:#22863a;">-5%</td></tr>
                </tbody>
              </table>
            </body>
            </html>
            HTML;
    }

    public static function newsletterHtml(): string
    {
        return <<<'HTML'
            <!DOCTYPE html>
            <html lang="en">
            <body style="margin:0; padding:0; background:#eef0f3; font-family:'Segoe UI', Arial, sans-serif; color:#1c1e21;">
              <table width="100%" cellpadding="0" cellspacing="0" style="background:#eef0f3;">
                <tr><td align="center" style="padding: 32px 16px;">
                  <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.06);">
                    <tr><td style="background:linear-gradient(90deg,#4f46e5,#06b6d4); padding:28px; color:#fff;">
                      <h1 style="margin:0; font-size:22px;">ADP Release 2.4</h1>
                      <p style="margin:6px 0 0 0; opacity:0.9;">April 2026 · Product newsletter</p>
                    </td></tr>
                    <tr><td style="padding:24px 28px;">
                      <h2 style="margin:0 0 12px 0; font-size:18px;">What's new</h2>
                      <ul style="margin:0 0 16px 0; padding-left:20px; line-height:1.6;">
                        <li>New <strong>Mailer</strong> collector across all adapters</li>
                        <li>Faster storage driver (SQLite backend)</li>
                        <li>MCP server with stdio + HTTP transports</li>
                      </ul>
                      <p style="margin:0 0 12px 0;">See attached files for release notes and a printable summary.</p>
                      <p style="margin:16px 0;">
                        <a href="https://example.com/changelog" style="background:#4f46e5; color:#fff; padding:10px 18px; text-decoration:none; border-radius:6px; display:inline-block;">Read full changelog</a>
                      </p>
                    </td></tr>
                    <tr><td style="background:#f6f7f9; padding:14px 28px; color:#6b7280; font-size:12px;">
                      You received this email as a fixture for ADP debugging. No real user action required.
                    </td></tr>
                  </table>
                </td></tr>
              </table>
            </body>
            </html>
            HTML;
    }

    public static function textAttachment(): string
    {
        return "ADP Release Notes\n=================\n\n- Added MailerCollector across Yii3, Symfony, Laravel and Yii2 adapters.\n- Normalized mail payload: from, to, subject, text/html, attachments.\n- Storage persists each sent message for inspection in the debug panel.\n";
    }

    public static function pdfAttachment(): string
    {
        return (string) base64_decode(self::PDF_BASE64, true);
    }
}

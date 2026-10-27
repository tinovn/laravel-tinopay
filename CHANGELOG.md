# Changelog

## v1.0.0 - 2026-10-27

- Webhook `POST /api/tinopay/webhook`, xác thực header `ALLPAY-TOKEN` / `Authorization: Apikey`, chống trùng theo `transaction_id`.
- Thêm `TinoPay::qrUrl()`, `TinoPay::banks()`, `TinoPay::transactions()`.

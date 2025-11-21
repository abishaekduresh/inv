# 🧾 Changelog

All notable changes to this project will be documented in this file.

This changelog follows the [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) format and adheres to [Semantic Versioning](https://semver.org/).

## [v2.1] - 2025-10-29

### 🚀 Added

#### Dashboard Enhancements

- Enhanced the dashboard overview to include:
  - **Total Invoices** and **Today’s Invoices**
  - **Total Sales** and **Today’s Sales**
  - **Total Businesses** and **Logs Count**
  - **Yesterday’s Sales** and **Yesterday’s Invoice Counts**
- Introduced filters for **Today**, **Yesterday**, **Daily**, **Monthly**, **Yearly**, and **Custom Date Range (From–To)** with dedicated **Filter** and **Refresh** buttons.
  - The **Refresh** button retrieves data from session storage if available, otherwise fetches from the API.
- Added **Business Analytics** section showing:
  - **Sales Overview Graph**
  - **Invoices Overview Graph**
- Implemented **Recent Invoices** cards to display the latest six invoices, including:
  - Invoice Number, Date, Place, Name, and Amount.

### 🐞 Fixed

- Added the `updated_at` column to the **business** table.

---

## 📘 API Reference

### 🔹 Dashboard Endpoint Response (Modified)

```http
GET /api/business/stats
```

**Description:**  
Updated API response format. Returns aggregated business summary, recent invoices, and chart data for various periods (today, yesterday, daily, monthly, yearly).

#### Example Response

```json
{
  "status": true,
  "message": "Yesterday dashboard stats fetched successfully.",
  "data": {
    "summary": {
      "totalInvoices": 2027,
      "todayInvoices": 0,
      "yesterdayInvoices": 1,
      "totalSales": "6,204,884.50",
      "todaySales": "0.00",
      "yesterdaySales": "10.00",
      "totalBusiness": 1,
      "totalLogs": 992
    },
    "recentInvoices": [
      {
        "invoiceId": "CDC3BDE2",
        "invoiceNumber": 3333,
        "invoiceDate": "2025-10-28",
        "name": "ABISHEK",
        "amount": "10.00",
        "place": "Tisayanvilai"
      }
    ],
    "chart": {
      "labels": [16],
      "invoices": [1],
      "sales": [10],
      "period": "yesterday",
      "fromDate": "2025-10-28",
      "toDate": "2025-10-28"
    }
  },
  "pagination": {
    "currentPage": 1,
    "limit": 1,
    "totalPages": 1,
    "totalRecords": 1
  }
}
```

---

## [v2.0] - 2025-10-28

### 🚀 Added

#### 🧩 Dashboard Enhancements

- Introduced **dynamic business dashboard** powered by Chart.js and Bootstrap.
- Added **real-time metrics API** for invoices, sales, business, and logs.
- Integrated **bar and line charts** for last 7-day invoice and sales tracking.
- Dashboard now caches data in **sessionStorage** for faster loading and offline view.
- Added **manual refresh** option to fetch live updates from the API.
- Added **responsive recent invoices cards** with count indicator and hover effects.
- Introduced **pagination** support for dashboard data (recent invoices).

#### 🧾 Activity Log Module

- Added new endpoint for **activity log fetching with pagination** and search.
- Integrated **Bootstrap table** UI for viewing logs with:
  - Search
  - Sort order (ASC/DESC)
  - Pagination controls
  - Record count display
- Added **JSON export** feature for logs.
- Implemented **debounced search** for improved UX.
- Created `fetchActivityLogs()` model method with proper pagination response.

#### ⚙️ Backend Improvements

- Enhanced **`fetchDashboardStats()`** method:
  - Real pagination support for recent invoices
  - Added `last7Days` analytics dataset for Chart.js
  - Unified data response with `data[]` and `pagination` block
- Improved `ActivityLoggerMiddleware` to log user actions, endpoints, and IP addresses.
- Added **error handling and standard JSON responses** for consistency.

#### 💅 UI/UX Enhancements

- Fully Bootstrap 5-based dashboard & settings pages.
- Added responsive **settings card** layout with logo upload preview.
- Implemented **SweetAlert2 toasts** for all success/error actions.
- Enhanced typography, spacing, and iconography consistency.
- Added refresh and export buttons with loading animations.

---

## 📘 API Reference

### 🔹 Dashboard Endpoint

```http
GET /api/business/stats
```

**Description:** Returns aggregated business statistics, recent invoices, and last 7-day chart data.

#### Response Format

```json
{
  "status": true,
  "message": "Dashboard stats fetched successfully.",
  "data": [
    {
      "totalInvoices": 2027,
      "todayInvoices": 1,
      "yesterdayInvoices": 1,
      "totalBusiness": 1,
      "totalSales": "6,204,884.50",
      "todaySales": "10.00",
      "yesterdaySales": "10.00",
      "totalLogs": 636,
      "recentInvoices": [...],
      "last7Days": {
        "labels": ["Wed","Thu","Fri","Sat","Sun","Mon","Tue"],
        "invoices": [0,0,0,0,0,1,1],
        "sales": [0,0,0,0,0,10,10]
      }
    }
  ],
  "pagination": {
    "currentPage": 1,
    "limit": 6,
    "totalPages": 338,
    "totalRecords": 2027
  }
}
```

---

### 🔹 Activity Logs Endpoint

```http
GET /api/business/activity/log
```

| Parameter | Type    | Description                                          |
| --------- | ------- | ---------------------------------------------------- |
| `q`       | string  | Search query across action, endpoint, user ID, or IP |
| `id`      | string  | Filter logs by business ID                           |
| `ord`     | string  | Order direction (`ASC` or `DESC`)                    |
| `page`    | integer | Pagination page number                               |
| `limit`   | integer | Number of records per page                           |

#### Example Response

```json
{
  "status": true,
  "message": "Activity logs fetched successfully.",
  "data": {
    "records": [
      {
        "userId": "95ABF2FD67AE",
        "businessId": "B3E531BB14BC",
        "action": "Activity logs fetched successfully.",
        "ipAddress": "::1",
        "createdAtText": "2025-10-28 12:29:49"
      }
    ]
  },
  "pagination": {
    "currentPage": 1,
    "limit": 25,
    "totalPages": 660,
    "totalRecords": 660
  }
}
```

---

### 🐞 Fixed

- Fixed redirect issues on SiteGround hosting.

📄 [View full version details →](documents/v2.0.md)

---

> **Last updated:** 2025-10-28  
> **Maintainer:** Abishaek Duresh B  
> 📧 abishaekduresh@gmail.com  
> 🌐 https://abishaek.com

---

## [v1.1] - 2025-10-27

### ✨ Updates

- Enhanced **Manage Invoice** table with **Tabular.js** integration for improved sorting and search functionality.
- Added **Settings → Business Information** update form with logo upload support.
- Integrated **SweetAlert** notifications for all CRUD actions.
- Added **Activity Logger Middleware**, `LoggerHelper`, and `ActionMapperHelper` for activity tracking in `activity_logs` table.
- Introduced a new **API endpoint** for real-time dashboard updates.
- Implemented `/uploads/{path:.*}` route to access uploaded files.
- Added **Business Controller** and **Business Model** with full CRUD API support.

📄 [View full version details →](documents/v1.1.md)

---

> 🧠 Tip: For future releases, create a new file under `documents/vX.X.md` for full details and link it here.

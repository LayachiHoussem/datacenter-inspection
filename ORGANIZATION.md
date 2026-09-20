# Project Organization Overview

## High‑Level Structure

| Layer | Directory | Typical Files | Responsibility |
|-------|-----------|---------------|----------------|
| **Routing / Front‑controller** | `public/index.php` | Route table (lines 47‑100) | Parses the request URI, matches it to a controller class & method, then dispatches. |
| **Controllers** | `app/controllers/` | `DashboardController.php`, `InspectionController.php`, … | Receives the request, runs business logic (often via services/helpers), then calls `view()` to render a template. |
| **Models** | `app/models/` | PDO‑based classes (`User.php`, `Inspection.php`, …) | Encapsulate database queries, return data objects/arrays. |
| **Services** | `app/services/` | PDF generation, file upload, reporting services | Re‑usable business logic that can be called from any controller. |
| **Helpers / Utilities** | `app/helpers/` | `functions.php`, `auth.php`, `validation.php`, `csrf.php` | Global functions (e.g. `auth_check()`, `view()`, `redirect()`) used throughout the app. |
| **Views (templates)** | `app/views/` | `layouts/`, `dashboard/`, `inspection/` etc. (PHP/HTML) | Pure presentation layer. Controllers invoke `view('folder/file', $data)` to load a view. |
| **Public assets** | `public/` | CSS, JS, images, uploaded files, `index.php` front‑controller | Exposed to the browser. |
| **Configuration** | `app/config/` | `constants.php`, `database.php` | Global constants, DB connection parameters. |
| **Storage** | `storage/` | Generated PDFs, log files, cache | Writable runtime data. |

---

## Request Flow (Typical)
1. **Browser → `public/index.php`** – URL is parsed (`$uri`).
2. **Router** looks up `$routes[$method][$uri]` → returns `[$controllerClass, $action]`.
3. **Controller** (`new $controllerClass()`) → calls `$controller->$action()`.
4. Inside the action you’ll usually:
   * Pull data via a **Model** or **Service**.
   * Prepare a `$data` array.
   * Call `view('path/to/template', $data)` – this loads a **View** file from `app/views/`.
5. The view renders HTML and is sent back to the client.

---

## Adding New Components
### New View (HTML template)
1. Create a template file under `app/views/` (e.g., `app/views/inspection/new_report.php`).
2. Add a method in the relevant controller:
   ```php
   // app/controllers/InspectionController.php
   public function newReport() {
       $data = []; // prepare data for the view
       view('inspection/new_report', $data);
   }
   ```
3. Register a route in `public/index.php`:
   ```php
   $routes['GET']['/inspections/new-report'] = ['App\\Controllers\\InspectionController', 'newReport'];
   ```

### New Function / Helper
* **Helper** – add to `app/helpers/functions.php` (or a new helper file) and `require_once` it in the front‑controller (lines 31‑36).
   ```php
   function format_date(string $dateStr): string {
       return date('d M Y', strtotime($dateStr));
   }
   ```
* **Service** – create a class in `app/services/` and autoload via Composer or the PSR‑4 loader.
   ```php
   // app/services/ReportService.php
   namespace App\\Services;

   class ReportService {
       public function generatePdf(array $data): string {
           // build PDF, return file path
       }
   }
   ```
   Then use it in a controller:
   ```php
   use App\\Services\\ReportService;

   public function exportPdf() {
       $service = new ReportService();
       $pdfPath = $service->generatePdf($someData);
       // send file to browser …
   }
   ```

---

## Quick File Links
- Front controller & routes: [public/index.php](file:///c:/wamp64/www/datacenter-inspection/public/index.php)
- Controllers directory: [app/controllers/](file:///c:/wamp64/www/datacenter-inspection/app/controllers/)
- Views directory: [app/views/](file:///c:/wamp64/www/datacenter-inspection/app/views/)
- Helpers (functions): [app/helpers/functions.php](file:///c:/wamp64/www/datacenter-inspection/app/helpers/functions.php) (if present)
- Services directory: [app/services/](file:///c:/wamp64/www/datacenter-inspection/app/services/)

---

*Use this file as a reference when deciding where to place new views, controllers, models, services, or helper functions.*

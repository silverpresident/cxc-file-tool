# Technical Guide - Shane's CXC File Tool

## Overview

This application is an ASP.NET Core MVC web application built using .NET 9 Preview. It aims to provide a tool for managing CXC SBA files, including uploading, renaming according to specific conventions, and downloading. It uses an abstracted storage mechanism, allowing deployment using either the local file system or Azure Blob Storage, configured via `appsettings.json`.

## Technology Stack

*   **Framework:** ASP.NET Core MVC (.NET 9 Preview)
*   **Language:** C# 12
*   **Data Storage:**
    *   **Development:** Local File System (`Data/` and `Data2/` directories within project root).
    *   **Production (Default):** Azure Blob Storage (Configurable via `appsettings.json`).
    *   **Format:** CSV Files.
*   **Frontend:** Bootstrap 5, jQuery, Bootstrap Icons
*   **Libraries:**
    *   `Azure.Storage.Blobs`: For Azure Blob Storage interaction.
    *   `CsvHelper`: For reading and writing CSV files.
    *   `BCrypt.Net-Next`: For password hashing.
    *   `MimeTypeMapOfficial`: For determining MIME types for file downloads.

## Project Structure (`cxc-tool-asp` folder)

*   **`/Controllers`**: Handles user requests. Now uses `IStorageService`.
    *   `AccountController`: Authentication.
    *   `AdminController`: Admin tasks (User, Candidate, Subject, File Mgmt). Includes `ImportDefaultSubjects` action.
    *   `HomeController`: Main landing page.
    *   `UploadController`: AJAX file uploads (multiple files, drag-drop, progress).
    *   `RenameController`: AJAX file renaming (single file, 4-digit candidate code input) & downloading (individual, all zip, by subject zip), inline file view.
*   **`/Data`**: (Local Storage Only) Stores candidate CSV (`{YEAR}cand.csv`) and user-specific file subdirectories. Corresponds to the 'data' Azure container.
*   **`/Data2`**: (Local Storage Only) Stores `users.csv` and `subjects.csv`. Corresponds to the 'data2' Azure container.
*   **`/Models`**: Data models (`User`, `Candidate`, `Subject`) and ViewModels. `Candidate` model includes `Exam` property. `Subject` model uses 8-digit `CxcSubjectCode`.
*   **`/Services`**: Business logic and data access abstractions.
    *   `IStorageService`: Interface defining storage operations.
    *   `LocalStorageService`: Implements `IStorageService` for local file system.
    *   `AzureBlobStorageService`: Implements `IStorageService` for Azure Blob Storage.
    *   `UserService`: Manages user data via `IStorageService`. Uses caching and write locks.
    *   `CandidateService`: Manages candidate data via `IStorageService`. Uses write locks. `CandidateImportMap` updated for flexible headers and 8-digit codes.
    *   `SubjectService`: Manages subject data via `IStorageService`. Uses write locks. `SubjectImportMap` updated for 8-digit codes.
*   **`/Views`**: Razor views.
    *   `/Upload/Index.cshtml`: Enhanced with drag-drop, AJAX uploads/deletes, progress bars.
    *   `/Rename/Index.cshtml`: Enhanced with AJAX rename/delete, file categorization, inline view button, 4-digit candidate code input.
*   **`/wwwroot`**: Static assets.
*   **`Program.cs`**: Entry point, DI registration (conditionally registers `IStorageService`), middleware config. Includes `--create-admin` command-line argument handling.
*   **`appsettings.json`**: Base configuration. Includes `StorageSettings` (defaults to "Azure").
*   **`appsettings.Development.json`**: Overrides `StorageSettings:StorageType` to "Local" for development.
*   **`*.csproj`**: Project file. Includes `Azure.Storage.Blobs` package reference. Includes `Data2/subjects.csv` for publishing.

## Data Management

*   **Storage Abstraction:** `IStorageService` allows swapping between `LocalStorageService` and `AzureBlobStorageService` via configuration. Services interact with `IStorageService`.
*   **Relative Paths:** Services use relative paths (e.g., "Data/UserFolder/file.txt", "Data2/users.csv"). `IStorageService` implementations handle mapping these to the actual storage location (local path or blob container/path).
*   **Users:** Stored at `Data2/users.csv`. Includes 8-digit `CxcSubjectCode`.
*   **Candidates:** Stored at `Data/{YEAR}cand.csv`. Includes `Exam` property. Import map handles `partyname` and alternative registration number headers.
*   **Subjects:** Stored at `Data2/subjects.csv`. Includes 8-digit `CxcSubjectCode`.
*   **Concurrency:** `SemaphoreSlim` is used only for *write* operations (Add, Update, Delete, Import, Save) in `UserService`, `CandidateService`, `SubjectService` to prevent file corruption. Read operations are lock-free to avoid hangs, accepting a small risk of read errors during concurrent writes. `UserService` also uses the lock to protect its cache consistency during writes.

## Authentication & Authorization

*   Standard ASP.NET Core Cookie Authentication.
*   Claims include Role ("Admin" or "User") and `FolderName`.

## File Naming Convention

Renamed files follow the format:
`{cxc_registration_no(10 digit)}{cxc_subject_code(8 digit)}{document-identifier}{file-extension}`

*   `{cxc_registration_no}` is constructed from `centreNumber(6) + candidateCode(4)`.
*   `{cxc_subject_code}` is the 8-digit code.
*   `{document-identifier}`: "CS", "MS", or "-1" (for Project).

## Key Implementation Details

*   **Conditional DI:** `Program.cs` registers `IStorageService` based on `StorageSettings:StorageType`.
*   **AJAX Enhancements:** Upload and Rename pages use `fetch` / `XMLHttpRequest` for asynchronous operations, dynamic UI updates, and progress display. Fallback to standard form posts remains.
*   **Initial Admin Creation:** Handled via `--create-admin` command-line argument in `Program.cs`.
*   **Subject Defaults Import:** `AdminController.ImportDefaultSubjects` copies the deployed `Data2/subjects.csv` to the configured storage using `IStorageService.CopyLocalFileToStorageAsync`.
*   **8-Digit Subject Codes:** Consistently used in models, validation, services, and file naming/filtering logic.

## Setup & Running

1.  Ensure .NET 9 SDK (Preview) is installed.
2.  Configure `appsettings.json` or environment variables:
    *   If using Azure: Ensure `StorageSettings:AzureBlobConnectionString` is correct and containers (`data`, `data2`) exist. `StorageType` should be "Azure".
    *   If using Local (Development): Ensure `StorageSettings:StorageType` is "Local" in `appsettings.Development.json`.
3.  Clone repository.
4.  Navigate to `cxc-tool-asp`.
5.  Run `dotnet restore`.
6.  **First Run / Setup:**
    *   Run `dotnet run -- --create-admin` and follow prompts to create the initial admin user.
    *   (Optional) If using Azure Blob Storage for the first time, run the application once with an admin login, navigate to Admin -> Subjects, and click "Import Defaults" to seed the `subjects.csv` in the blob container.
7.  Run `dotnet run` to start the web server.
8.  Access via the provided URL (e.g., `https://localhost:xxxx`).

## Future Considerations / TODOs

*   Refine AJAX error handling and user feedback on Upload/Rename pages.
*   Implement remaining UI elements on Rename page (Processed Files section interaction).
*   Add file validation (type/size) on the client-side JavaScript.
*   Consider if deleting a user should delete their storage folder (`IStorageService.DeleteDirectoryAsync`).
*   Add comprehensive logging configuration (e.g., Serilog, Application Insights).
*   Implement unit/integration tests, especially for `IStorageService` implementations and CSV services.

*(This guide provides a high-level technical overview.)*

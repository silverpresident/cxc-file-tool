# AI Knowledge Base - Shane's CXC File Tool

This document stores contextual information, design decisions, and learnings relevant to AI development and maintenance of this project.

## Project Context

*   **Goal:** Migrate a PHP-based CXC SBA file management tool to ASP.NET Core MVC (.NET 9 Preview).
*   **Core Functionality:** User authentication, CSV-based data management (Users, Candidates, Subjects), file upload, file renaming based on CXC convention, file download (individual/zip).
*   **Target Users:** Teachers/Administrators managing SBA submissions.
*   **Storage:** Uses `IStorageService` abstraction to support Local File System (Development) or Azure Blob Storage (Production), configured via `appsettings.json`.

## Design Decisions & Rationale

*   **Framework Choice (.NET 9 Preview):** Chosen as per user request.
*   **Storage Abstraction (`IStorageService`):** Introduced to decouple services from the underlying storage mechanism (Local vs. Azure). Implementations (`LocalStorageService`, `AzureBlobStorageService`) handle the specifics. Registered conditionally in `Program.cs` based on `StorageSettings:StorageType`.
*   **CSV Data Storage:** A core requirement. Handled via `IStorageService`. `CsvHelper` used for parsing/writing.
*   **Relative Paths:** Services operate using relative paths (e.g., "Data/UserFolder/file.txt", "Data2/subjects.csv"). `IStorageService` implementations map these to physical paths or blob paths/containers.
*   **Data/Data2 Separation:** `users.csv` and `subjects.csv` use the "Data2" path prefix (mapped to `data2` container in Azure or `Data2/` locally) to protect them from admin bulk file operations targeting the "Data" path prefix (mapped to `data` container or `Data/` locally).
*   **Service Lifetime (Singleton):** Services (`UserService`, `CandidateService`, `SubjectService`, `IStorageService` implementations) registered as Singletons. Suitable for shared resources like files/blob clients and managing state (locks/caches).
*   **Password Hashing:** `BCrypt.Net-Next`.
*   **Subject Codes (8-Digit):** The application now consistently uses 8-digit subject codes based on user feedback, aligning with the source PHP data. Model validation, file naming logic (`RenameController`, `FileService`), filtering logic (`RenameController`), and the initial `subjects.csv` were updated.
*   **File Naming Convention:** Implemented as `{RegNo(10)}{SubjectCode(8)}{DocId}{Extension}`. `RegNo` is constructed from `CentreNo(6) + CandidateCode(4)`. `DocId` is "CS", "MS", or "-1".
*   **Concurrency Locking (`SemaphoreSlim`):** Locks are used *only* for write operations (Add, Update, Delete, Import, Save) in CSV services (`UserService`, `CandidateService`, `SubjectService`) to prevent file corruption. Read operations are lock-free to prevent hangs, accepting a minor risk of read errors during concurrent writes. `UserService` also locks around cache modifications during writes.
*   **AJAX Enhancements (Upload/Rename):** Implemented using `fetch`/`XMLHttpRequest` for file uploads (multiple, drag-drop, progress) and rename/delete operations to provide a smoother UX without full page reloads. Controllers return JSON for AJAX requests.
*   **Initial Admin Creation:** Handled via `--create-admin` command-line argument in `Program.cs`.
*   **Subject Defaults Import:** `AdminController.ImportDefaultSubjects` action added. Uses `IStorageService.CopyLocalFileToStorageAsync` to copy the deployed `Data2/subjects.csv` to the configured storage (local or Azure).
*   **Candidate Import Mapping:** `CandidateImportMap` updated for flexible header names (`partyname`, `current_cxc_candidate_no`, etc.) and desired column order.
*   **Inline File View:** `RenameController.GetFileInline` action added to serve files with `Content-Disposition: inline`.

## Learnings & Challenges

*   **.NET 9 Preview Templates:** `--use-program-main` flag behavior differs from older templates.
*   **Required Properties (`required`):** Need careful initialization.
*   **Tag Helper Attribute Syntax:** Conditional attributes require specific syntax (`attribute="@(condition ? value : null)"`).
*   **Namespace Discrepancies:** Corrected `MimeTypeMapOfficial` namespace to `MimeTypes`.
*   **Concurrency Issues:** Initial locking strategy caused hangs; revised to lock only write operations on CSV files. Read operations are now lock-free.
*   **Refactoring Complexity:** Introducing `IStorageService` required careful refactoring across multiple services and controllers. `replace_in_file` struggled with large changes, necessitating `write_to_file` fallback.
*   **AJAX Implementation:** Requires careful handling of form data, anti-forgery tokens, progress events (XHR), and dynamic UI updates in JavaScript. Controller actions need modification to return JSON.
*   **Subject Code Discrepancy:** Initial implementation used 6-digit codes based on convention, but source data used 8 digits. Refactored to use 8 digits consistently.

## Future Development Notes for AI

*   **Rename Page UI:** Implement dynamic updates for the "Processed Files" list fully.
*   **Error Handling:** Improve specificity and user feedback. Implement global handler.
*   **File Validation:** Add client-side validation.
*   **Admin User File Deletion:** Decide and implement policy for deleting user files when deleting a user account.
*   **Logging:** Configure structured logging (e.g., Serilog).
*   **Testing:** Add unit/integration tests, especially for storage interactions and CSV logic.
*   **Configuration:** Externalize more settings (e.g., max file size, allowed extensions).
*   **Database Migration:** Still a consideration for future scalability.

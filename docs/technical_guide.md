# Technical Guide - Shane's CXC File Tool

## Overview

This application is an ASP.NET Core MVC web application built using .NET 9 Preview. It aims to provide a tool for managing CXC SBA files, including uploading, renaming according to specific conventions, and downloading. It uses CSV files for data persistence instead of a traditional database.

## Technology Stack

*   **Framework:** ASP.NET Core MVC (.NET 9 Preview)
*   **Language:** C# 12
*   **Data Storage:** CSV Files (`Data/` and `Data2/` directories)
*   **Frontend:** Bootstrap 5, jQuery, Bootstrap Icons
*   **Libraries:**
    *   `CsvHelper`: For reading and writing CSV files.
    *   `BCrypt.Net-Next`: For password hashing.
    *   `MimeTypeMapOfficial`: For determining MIME types for file downloads.

## Project Structure (`cxc-tool-asp` folder)

*   **`/Controllers`**: Contains MVC controllers handling user requests and orchestrating responses.
    *   `AccountController`: Handles authentication.
    *   `AdminController`: Handles administrative tasks (user, candidate, subject, file management).
    *   `HomeController`: Handles the main authenticated landing page.
    *   `UploadController`: Handles user file uploads.
    *   `RenameController`: Handles file renaming and downloading logic.
*   **`/Data`**: Stores application data CSV files (`{YEAR}cand.csv`) and user-specific file subdirectories (named by `User.FolderName`). **Admin users can delete contents here.**
*   **`/Data2`**: Stores the  (`subjects.csv`,`users.csv`) files. Separated to prevent accidental deletion by admin file management actions targeting the `/Data` folder.
*   **`/Models`**: Contains data models (records like `User`, `Candidate`, `Subject`) and view models (`LoginViewModel`, `UserViewModel`).
*   **`/Services`**: Contains service interfaces and implementations for business logic and data access.
    *   `UserService`: Manages user data and authentication logic.
    *   `CandidateService`: Manages candidate data.
    *   `SubjectService`: Manages subject data.
    *   `FileService`: Manages file system operations.
*   **`/Views`**: Contains Razor views for rendering HTML. Organized by controller.
    *   `/Shared`: Contains layout files (`_Layout.cshtml`), partial views (`_LoginPartial.cshtml`, `_ValidationScriptsPartial.cshtml`), and the error view.
*   **`/wwwroot`**: Contains static assets (CSS, JavaScript, libraries).
*   **`Program.cs`**: Application entry point, service registration (DI), and middleware pipeline configuration.
*   **`appsettings.json`**: Configuration settings (though minimal in this version).
*   **`*.csproj`**: Project file defining dependencies and build settings.

## Data Management

*   **Users:** Stored in `Data2/users.csv`. Includes `Id` (Guid), `DisplayName`, `PasswordHash` (BCrypt), `FolderName` (generated, unique), `IsAdmin` (bool).
*   **Candidates:** Stored in `Data/{YEAR}cand.csv`. Includes `Name`, `CxcRegistrationNo` (10 digits, primary key), `Class`, `Subjects` (comma-separated string).
*   **Subjects:** Stored in `Data2/subjects.csv`. Includes `Name`, `CxcSubjectCode` (8 digits, primary key), `Level` (enum: CSEC, CAPE).
*   **Files:** User-uploaded files are stored in subdirectories within `Data/`. The subdirectory name corresponds to the `User.FolderName`.

**Note:** CSV data services (`UserService`, `CandidateService`, `SubjectService`) use `CsvHelper` and implement basic file locking (`SemaphoreSlim`) to mitigate potential concurrency issues during read/write operations. `UserService` also uses a simple `ConcurrentDictionary` for in-memory caching.

## Authentication & Authorization

*   Uses standard ASP.NET Core Cookie Authentication.
*   Login credentials (`DisplayName`, `Password`) are validated against `users.csv` by `UserService`.
*   Successful login creates a claims principal including `NameIdentifier` (User ID), `Name` (Display Name), `Role` ("Admin" or "User"), and `FolderName`.
*   Authorization is handled via `[Authorize]` attributes on controllers/actions, sometimes specifying `Roles = "Admin"`.

## File Naming Convention

Renamed files follow the format:
`{cxc_registration_no(10 digit)}{cxc_subject_code(6 digit)}{document-identifier}{file-extension}`

*   `{document-identifier}`:
    *   `CS`: Cover Sheet
    *   `MS`: Mark Scheme
    *   `-1`, `-2`, ...: Project files (sequentially numbered per rename operation if multiple project files are selected).

## Key Implementation Details

*   **Dependency Injection:** Services are registered as Singletons in `Program.cs` due to their file-based nature and internal state/locking.
*   **ViewModels:** Used for forms (`LoginViewModel`, `UserViewModel`) to handle specific UI requirements and validation separate from domain models.
*   **Shared Forms:** `UserForm.cshtml`, `CandidateForm.cshtml`, `SubjectForm.cshtml` are used for both Add and Edit operations.
*   **TempData:** Used for displaying success/error messages after POST-Redirect-GET operations.
*   **File Handling:** `IFormFile` is used for uploads. `FileStreamResult` is used for downloads. `System.IO.Compression` is used for creating zip archives.

## Setup & Running

1.  Ensure the .NET 9 SDK (Preview) is installed.
2.  Clone the repository.
3.  Navigate to the `cxc-tool-asp` directory in your terminal.
4.  Run `dotnet restore` to install dependencies.
5.  Run `dotnet run` to start the application.
6.  Access the application via the URL provided in the console output (usually `https://localhost:xxxx` and `http://localhost:yyyy`).
7.  **First Run:** No users exist initially. An administrator needs to be created, potentially by manually editing the `Data2/users.csv` file or adding a temporary registration/seeding mechanism (not currently implemented).

## Future Considerations / TODOs

*   Implement the "Processed Files" display section on the Rename page.
*   Add more robust validation (file types, sizes).
*   Implement database storage instead of CSV for better scalability and data integrity.
*   Refine error handling and user feedback.
*   Add comprehensive logging configuration.
*   Implement unit and integration tests.
*   Improve UI/UX and mobile responsiveness.
*   Consider security hardening (e.g., input sanitization beyond basic path checks).
*   Add a mechanism for initial admin user creation.

*(This guide provides a high-level technical overview.)*

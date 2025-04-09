# AI Knowledge Base - Shane's CXC File Tool

This document stores contextual information, design decisions, and learnings relevant to AI development and maintenance of this project.

## Project Context

*   **Goal:** Migrate a PHP-based CXC SBA file management tool to ASP.NET Core MVC (.NET 9 Preview).
*   **Core Functionality:** User authentication, CSV-based data management (Users, Candidates, Subjects), file upload, file renaming based on CXC convention, file download (individual/zip).
*   **Target Users:** Teachers/Administrators managing SBA submissions.
*   **Key Constraint:** No traditional database; all data persistence uses CSV files.

## Design Decisions & Rationale

*   **Framework Choice (.NET 9 Preview):** Chosen as per user request, despite .NET 8 being the current LTS. This implies acceptance of potential preview version instability or changes.
*   **CSV Data Storage:** A core requirement. This necessitates careful file handling, locking (`SemaphoreSlim`) to prevent race conditions, and potentially less efficient querying compared to a database. `CsvHelper` library chosen for its robustness.
*   **User Data Separation (`Data2` folder):** The `users.csv` file is stored separately from the main `Data` folder to protect it from the Admin's "Manage All Files" delete capability, which operates on the `Data` folder and its subdirectories.
*   **Service Lifetime (Singleton):** Services managing CSV files (`UserService`, `CandidateService`, `SubjectService`, `FileService`) are registered as Singletons. This is suitable because they manage shared file resources and contain state (like file locks or caches - `UserService`). Using Scoped or Transient could lead to issues with file locking or inconsistent caching.
*   **Password Hashing:** `BCrypt.Net-Next` used for secure password hashing.
*   **File Naming Convention:** Implemented as `{RegNo(10)}{SubjectCode(6)}{DocId}{Extension}`. `DocId` is "CS", "MS", or "-N" for projects. `FileService.RenameFileAsync` handles this.
*   **Admin vs. User Roles:** Simple role system based on `User.IsAdmin` boolean flag, translated into a "Admin" or "User" role claim during login for use with `[Authorize(Roles = "Admin")]`.
*   **Unique Folder Names (`UserService.GenerateUniqueFolderNameAsync`):** A simple 2-letter or 2-letter + 1-digit scheme is used. Collision checking includes existing user records *and* existing directories in `/Data` to handle potential orphaned folders. Max attempts limit prevents infinite loops in unlikely scenarios.
*   **CSV Import Mapping (`CandidateImportMap`, `SubjectImportMap`):** Flexible mapping using `CsvHelper ClassMap` and `PrepareHeaderForMatch` allows importing CSVs with slightly different header names (case-insensitive, ignoring spaces/\_).
*   **MIME Types:** `MimeTypeMapOfficial` library used for setting correct content types during file downloads.

## Learnings & Challenges

*   **.NET 9 Preview Templates:** The `--use-program-main` flag did not create a separate `Startup.cs` as expected with older templates; configuration remained within `Program.cs` using `WebApplicationBuilder`. Adapted the plan accordingly.
*   **Required Properties (`required` keyword):** Need careful initialization in constructors or object initializers, especially when creating view models for 'Add' actions (e.g., `UserViewModel.DisplayName`, `Subject.Level`).
*   **Tag Helper Attribute Syntax:** Conditional rendering of HTML attributes like `readonly` requires specific syntax (e.g., `readonly="@(isEditMode ? "readonly" : null)"`) rather than embedding C# directly in the attribute area.
*   **Namespace Discrepancies:** Initial confusion over the correct namespace for the `MimeTypeMapOfficial` package (`MimeTypes` vs. `MimeTypeMap`). Corrected to `using MimeTypeMap;`.
*   **CSV Concurrency:** Using `SemaphoreSlim` provides basic locking but might become a bottleneck under high load (unlikely for this application type). A database would handle concurrency more robustly.
*   **Admin File Deletion Scope:** The requirement for Admins to delete *all* files in `/Data` necessitated moving `users.csv` to `/Data2`. This adds slight complexity but meets the safety requirement.

## Future Development Notes for AI

*   **Rename Page UI:** The "Processed Files" section needs implementation. Consider using AJAX to update the file list after renaming without a full page refresh. Displaying both original and renamed files might be useful temporarily.
*   **Error Handling:** Implement more specific error handling and user-friendly messages, possibly using a dedicated error logging framework.
*   **Initial Admin User:** Add a setup step or command-line utility to create the first admin user, as manual CSV editing is error-prone.
*   **File Validation:** Add server-side validation for uploaded file types and sizes in `UploadController`.
*   **Refactoring:** Consider extracting common CRUD logic from `AdminController` into generic helper methods or base classes if complexity increases.
*   **Testing:** Prioritize testing for CSV services (`UserService`, `CandidateService`, `SubjectService`) and `FileService` due to file I/O and locking. Test edge cases for renaming and CSV imports.

# API Documentation - Shane's CXC File Tool

This document outlines the controllers and services within the application.

## Controllers

### `AccountController`
- Handles user login and logout processes.
- **Actions:**
    - `Login` (GET): Displays the login form.
    - `Login` (POST): Validates credentials, creates authentication cookie, and redirects.
    - `Logout` (POST): Signs the user out and redirects.
    - `AccessDenied` (GET): Displays the access denied page.

### `AdminController`
- Provides administrative functionalities. Requires "Admin" role authorization.
- Injects `IUserService`, `ICandidateService`, `ISubjectService`, `IStorageService`.
- **Actions:**
    - `Users` (GET): Displays the user management page.
    - `AddUser` (GET): Displays the form to add a new user.
    - `AddUser` (POST): Processes the addition of a new user.
    - `EditUser` (GET): Displays the form to edit an existing user.
    - `EditUser` (POST): Processes the update of an existing user.
    - `DeleteUser` (POST): Deletes a specified user.
    - `Candidates` (GET): Displays the candidate management page.
    - `AddCandidate` (GET): Displays the form to add a new candidate.
    - `AddCandidate` (POST): Processes the addition of a new candidate.
    - `EditCandidate` (GET): Displays the form to edit an existing candidate (identified by 8-digit RegNo).
    - `EditCandidate` (POST): Processes the update of an existing candidate.
    - `DeleteCandidate` (POST): Deletes a specified candidate (identified by 8-digit RegNo).
    - `DeleteAllCandidates` (POST): Deletes all candidates for the current year from storage.
    - `UploadCandidates` (POST): Processes the upload of a candidate CSV file, replacing existing data in storage.
    - `DownloadCandidates` (GET): Provides the current candidate list from storage as a CSV download.
    - `Subjects` (GET): Displays the subject management page.
    - `AddSubject` (GET): Displays the form to add a new subject.
    - `AddSubject` (POST): Processes the addition of a new subject.
    - `EditSubject` (GET): Displays the form to edit an existing subject (identified by 8-digit SubjectCode).
    - `EditSubject` (POST): Processes the update of an existing subject.
    - `DeleteSubject` (POST): Deletes a specified subject (identified by 8-digit SubjectCode).
    - `DeleteAllSubjects` (POST): Deletes all subjects from storage.
    - `UploadSubjects` (POST): Processes the upload of a subject CSV file, replacing existing data in storage.
    - `DownloadSubjects` (GET): Provides the current subject list from storage as a CSV download.
    - `ImportDefaultSubjects` (POST): Copies the default `subjects.csv` from the application deployment to storage, overwriting the existing one.
    - `Files` (GET): Displays a list of all files across all user folders by querying storage.
    - `DeleteFile` (POST): Deletes a specific file from a specific user folder in storage.
    - `DownloadFile` (GET): Provides a specific file from a specific user folder from storage for download.

### `HomeController`
- Handles the main landing page after login. Requires authentication.
- **Actions:**
    - `Index` (GET): Displays the welcome page.
    - `Error` (GET): Displays the error page (allows anonymous access).

### `UploadController`
- Handles the initial file upload process for authenticated users. Uses AJAX with fallback.
- Injects `IStorageService`.
- **Actions:**
    - `Index` (GET): Displays the upload form and lists files currently in the user's storage folder.
    - `UploadFile` (POST): Processes the upload of single or multiple files via AJAX or form post. Returns JSON for AJAX. Includes file type/size validation.
    - `DeleteUploadedFile` (POST): Deletes a specific file from the user's storage folder via AJAX or form post. Returns JSON for AJAX.

### `RenameController`
- Handles the file renaming and downloading process for authenticated users. Uses AJAX with fallback for rename/delete.
- Injects `IStorageService`, `ICandidateService`, `ISubjectService`.
- **Actions:**
    - `Index` (GET): Displays the renaming interface, listing unprocessed and processed files from the user's storage folder.
    - `RenameFile` (POST): Processes the renaming of a single selected file based on provided parameters (including 4-digit candidate code). Returns JSON for AJAX.
    - `GetFileInline` (GET): Retrieves a file from storage and returns it for inline display in the browser.
    - `DownloadFile` (GET): Provides a specific file from the user's storage folder for download.
    - `DownloadAllFiles` (GET): Provides a zip archive of all files in the user's storage folder.
    - `DownloadFilesBySubject` (GET): Provides a zip archive of files matching a specific 8-digit subject code in the user's storage folder.
    - `DeleteFile` (POST): Deletes a specific file from the user's storage folder via AJAX or form post. Returns JSON for AJAX.

## Services

### `IStorageService`
- Abstraction interface for file storage operations (local or Azure).
- Defines methods for saving, reading, deleting, moving, listing files/directories, checking existence, getting URLs, and copying local files.
- Paths are relative (e.g., "Data/UserFolder/file.txt", "Data2/users.csv").
- Includes methods `GetDataFolderName()` and `GetPrivateDataFolderName()` to get base relative paths.

### `LocalStorageService` : `IStorageService`
- Implements `IStorageService` using the local file system relative to the application's `ContentRootPath`.

### `AzureBlobStorageService` : `IStorageService`
- Implements `IStorageService` using Azure Blob Storage.
- Reads connection string and container names (`data`, `data2`) from configuration (`StorageSettings`).
- Maps relative paths "Data/..." to the data container and "Data2/..." to the data2 container.

### `IUserService` / `UserService`
- Manages user data stored via `IStorageService` at `Data2/users.csv`.
- Handles CRUD operations, password validation (BCrypt hashing), and unique folder name generation (checks storage via `IStorageService`).
- Uses `CsvHelper` for CSV interaction.
- Implements in-memory caching (`ConcurrentDictionary`) with locking (`SemaphoreSlim`) for write operations and cache modifications. Read operations on the cache itself are lock-free, but loading the cache from storage is not locked.

### `ICandidateService` / `CandidateService`
- Manages candidate data stored via `IStorageService` at `Data/{YEAR}cand.csv`.
- Handles CRUD operations, CSV import/export (using an updated `CandidateImportMap` for flexible headers and 8-digit codes).
- Uses `CsvHelper` for file interaction.
- Implements file locking (`SemaphoreSlim`) for write operations. Read operations are not locked.

### `ISubjectService` / `SubjectService`
- Manages subject data stored via `IStorageService` at `Data2/subjects.csv`.
- Handles CRUD operations, CSV import/export (using `SubjectImportMap` and 8-digit codes).
- Uses `CsvHelper` for file interaction.
- Implements file locking (`SemaphoreSlim`) for write operations. Read operations are not locked.

*(This documentation is preliminary and should be expanded with more detail on parameters, return types, and error handling.)*

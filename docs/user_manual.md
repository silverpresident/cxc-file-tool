# User Manual - Shane's CXC File Tool

## Introduction

Shane's CXC File Tool helps manage Caribbean Examinations Council (CXC) School-Based Assessment (SBA) files, including cover sheets, mark schemes, and project files. Users can upload files, preview them, rename them according to the CXC naming convention, download individual files, and download zip archives. Administrators can also manage users, candidate lists, subject lists, and uploaded files across all user folders.

## Getting Started

### Logging In
1. Navigate to the application's URL.
2. Click the "Login" link in the top-right corner.
3. Enter your assigned **Display Name** and **Password**.
4. Optionally, check "Remember me?" to stay logged in across browser sessions.
5. Click the "Log in" button.

Upon successful login, you will be redirected to the Home page.

**First-time setup:** If no administrator exists yet, create the initial admin account from the command line:

```bash
dotnet run -- --create-admin
```

Run the command from the `cxc-tool-asp` directory and follow the prompts.

## User Features

### Home Page
- Displays a welcome message after login.
- Provides access to Upload, Rename & Download, and available Admin pages through the navigation bar.

### Upload Files
1. Click the "Upload" link in the navigation bar.
2. Drag and drop one or more files onto the upload area, or click "Choose Files..." and select files from your computer.
3. Uploads start automatically when JavaScript is enabled. A progress bar appears for each file.
4. Success or error messages appear on the page and dismiss automatically after 30 seconds.
5. Uploaded files are listed under "Your Uploaded Files".
6. Use the trash icon to delete one file, or "Delete All" to remove all files in your upload folder.
7. After uploading, go to "Rename & Download" to process the files.

Allowed upload types are PDF, DOCX, XLSX, JPG, JPEG, PNG, ZIP, MP3, MP4, M4A, and M4V. The maximum size is 20 MB per file. The server enforces these limits even if the browser does not catch them first.

### Rename & Download Files
1. Click the "Rename & Download" link in the navigation bar.
2. The page is split into "Rename Unprocessed File" and "Manage Processed Files".
3. Under "Select File to Process", choose one unprocessed file. Use the eye icon to open the file in a new tab before renaming.
4. Enter the renaming details:
   - **Subject:** Select the correct subject. The application uses the subject's 8-digit CXC code in the final filename.
   - **Centre Number:** Confirm the 6-digit centre number. The default is `100111`.
   - **Candidate Code:** Select a candidate from the list or enter the 4-digit candidate code. The application combines the centre number and candidate code to form the 10-digit registration number.
   - **Document Type:** Select Cover Sheet (`CS`), Mark Scheme (`MS`), or Project File.
5. Click "Rename Selected File".
6. On success, the file moves from the unprocessed list to the processed list and the success message remains visible for 60 seconds.
7. On failure, check the error message and confirm that all fields are complete, the codes are valid, and the target filename does not already exist.

Processed filenames use this pattern:

```text
{10-digit registration number}{8-digit subject code}{document identifier}{extension}
```

For cover sheets and mark schemes, the document identifier is `CS` or `MS`. For project files, the application assigns the next available project number for the same candidate and subject, such as `-1`, `-2`, or `-3`.

In "Manage Processed Files", you can:

- View a file in a new tab.
- Download an individual file.
- Delete an individual file.
- Click "Download All (.zip)" to download a zip archive of all files currently in your folder.
- Use "Download by Subject" to download a zip archive of processed files matching a selected subject.

## Administrator Features

Administrators have access to all user features plus the following sections under the "Admin" menu:

### Manage Users
- **View:** Lists all registered users, their system-generated folder name, and admin status.
- **Add:** Click "Add New User". Fill in the Display Name, Password (and confirm), and check "Is Administrator?" if needed. Click "Add User".
- **Edit:** Click "Edit". Modify the Display Name or admin status. Enter a new password to change it, or leave the password fields blank to keep the current password.
- **Delete:** Click "Delete" and confirm the action. Deleting a user removes the user record only; it does not delete that user's storage folder or uploaded files.

### Manage Candidates (`Data/{YEAR}cand.csv`)
- **View:** Lists candidates for the current year.
- **Add:** Click "Add New Candidate". Fill in the 10-digit registration number, name, optional class, and optional subjects.
- **Edit:** Click "Edit". Modify the candidate details. The registration number cannot be changed after creation.
- **Delete:** Click "Delete". Confirm deletion.
- **Upload CSV List:** Upload a CSV file to import candidates for the current year. The importer accepts several common header names, including `name`/`partyname` and `cxcregistrationno`/`cxc_candidate_no`/`current_cxc_candidate_no`.
- **Download CSV List:** Downloads the current list.
- **Delete All Candidates:** Removes all candidates for the current year.

### Manage Subjects (`Data2/subjects.csv`)
- **View:** Lists all subjects.
- **Add:** Click "Add New Subject". Fill in 8-digit Subject Code, Name, Level. Click "Add Subject".
- **Edit:** Click "Edit". Modify Name or Level. Subject Code cannot be changed. Click "Save Changes".
- **Delete:** Click "Delete". Confirm deletion.
- **Upload CSV List:** Upload a CSV file to replace the subject list.
- **Download CSV List:** Downloads the current list.
- **Delete All Subjects:** Removes all subjects.
- **Import Defaults:** Overwrites the current subject list in storage with the default list included with the application. Use this if the list becomes corrupted or needs resetting.

### Manage All Files (`Data/` subfolders)
- **View:** Lists all files across all user folders.
- **Download:** Click "Download" next to any file.
- **Delete:** Click "Delete" next to any file. Confirm deletion.
- **Delete All:** Removes all files from all user folders. Use this carefully.

## Troubleshooting

- **Login Failed:** Double-check Display Name/Password. Use the `--create-admin` command if no admin exists.
- **File Upload Failed:** Check that the file type is allowed and the file is no larger than 20 MB. Review the on-screen error message for the specific reason.
- **Renaming Failed:** Ensure one file is selected, all required fields are filled, the candidate code is 4 digits, the centre number is 6 digits, and the subject code is valid. Also check whether the target filename already exists.
- **Download Failed:** File may have been deleted or an error occurred. Check error messages.
- **No Candidates or Subjects Available:** Ask an administrator to import or create the candidate and subject lists before renaming files.

*(This manual should be updated whenever user-facing workflows, limits, or supported file types change.)*

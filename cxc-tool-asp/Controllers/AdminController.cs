using cxc_tool_asp.Models; // Add model namespace
using cxc_tool_asp.Services;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;

namespace cxc_tool_asp.Controllers;

[Authorize(Roles = "Admin")] // Only allow users with the "Admin" role
public class AdminController : Controller
{
    private readonly IUserService _userService;
    private readonly ICandidateService _candidateService;
    private readonly ISubjectService _subjectService;
    private readonly IFileService _fileService;
    private readonly ILogger<AdminController> _logger;

    public AdminController(
        IUserService userService,
        ICandidateService candidateService,
        ISubjectService subjectService,
        IFileService fileService,
        ILogger<AdminController> logger)
    {
        _userService = userService;
        _candidateService = candidateService;
        _subjectService = subjectService;
        _fileService = fileService;
        _logger = logger;
    }

    // GET: /Admin/Users
    public async Task<IActionResult> Users()
    {
        // TODO: Implement user listing view
        var users = await _userService.GetAllUsersAsync();
        _logger.LogInformation("Admin accessed User Management page.");
        // Pass users to the view
        return View(users);
    }

    // GET: /Admin/AddUser
    public IActionResult AddUser()
    {
        // Initialize required properties for the view model
        return View("UserForm", new UserViewModel { DisplayName = string.Empty });
    }

    // POST: /Admin/AddUser
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> AddUser(UserViewModel model)
    {
        if (string.IsNullOrWhiteSpace(model.Password) || model.Password != model.ConfirmPassword)
        {
            ModelState.AddModelError("Password", "Password and confirmation password are required and must match.");
        }

        if (ModelState.IsValid)
        {
            var existingUser = await _userService.GetUserByDisplayNameAsync(model.DisplayName);
            if (existingUser != null)
            {
                ModelState.AddModelError("DisplayName", "This display name is already taken.");
            }
            else
            {
                var newUser = await _userService.AddUserAsync(model);
                if (newUser != null)
                {
                    _logger.LogInformation("Admin '{AdminUser}' added new user '{NewUser}'.", User.Identity?.Name, model.DisplayName);
                    TempData["UserMessage"] = $"User '{model.DisplayName}' added successfully.";
                    return RedirectToAction(nameof(Users));
                }
                else
                {
                    ModelState.AddModelError(string.Empty, "Failed to add user. Could not generate unique folder or save failed.");
                     _logger.LogError("Failed to add user '{NewUser}' via admin '{AdminUser}'.", model.DisplayName, User.Identity?.Name);
                }
            }
        }

        // If we got this far, something failed, redisplay form
        return View("UserForm", model);
    }

    // GET: /Admin/EditUser/{id}
    public async Task<IActionResult> EditUser(Guid id)
    {
        var user = await _userService.GetUserByIdAsync(id);
        if (user == null)
        {
            _logger.LogWarning("Admin '{AdminUser}' attempted to edit non-existent user with ID '{UserId}'.", User.Identity?.Name, id);
            return NotFound(); // Or redirect to Users list with an error message
        }

        var model = new UserViewModel
        {
            Id = user.Id,
            DisplayName = user.DisplayName,
            IsAdmin = user.IsAdmin
            // Password fields are left null intentionally for edit mode
        };

        return View("UserForm", model);
    }

    // POST: /Admin/EditUser/{id}
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> EditUser(Guid id, UserViewModel model)
    {
        if (id != model.Id)
        {
            return BadRequest("User ID mismatch.");
        }

        // Check password confirmation only if a new password is provided
        if (!string.IsNullOrWhiteSpace(model.Password) && model.Password != model.ConfirmPassword)
        {
            ModelState.AddModelError("Password", "New password and confirmation password must match.");
        }

        // Fetch the original user to check for display name conflicts if it changed
        var originalUser = await _userService.GetUserByIdAsync(id);
        if (originalUser == null)
        {
             return NotFound(); // Should not happen if GET worked, but good check
        }

        if (ModelState.IsValid)
        {
             // Check if display name changed and conflicts with another user
             if (!originalUser.DisplayName.Equals(model.DisplayName, StringComparison.OrdinalIgnoreCase))
             {
                 var conflictingUser = await _userService.GetUserByDisplayNameAsync(model.DisplayName);
                 if (conflictingUser != null && conflictingUser.Id != id)
                 {
                     ModelState.AddModelError("DisplayName", "This display name is already taken by another user.");
                 }
             }

             if (ModelState.IsValid) // Re-check after potential display name error
             {
                var success = await _userService.UpdateUserAsync(model);
                if (success)
                {
                    _logger.LogInformation("Admin '{AdminUser}' updated user '{UpdatedUser}' (ID: {UserId}).", User.Identity?.Name, model.DisplayName, id);
                    TempData["UserMessage"] = $"User '{model.DisplayName}' updated successfully.";
                    return RedirectToAction(nameof(Users));
                }
                else
                {
                    ModelState.AddModelError(string.Empty, "Failed to update user. Please try again.");
                    _logger.LogError("Failed to update user '{UpdatedUser}' (ID: {UserId}) via admin '{AdminUser}'.", model.DisplayName, id, User.Identity?.Name);
                }
             }
        }

        // If we got this far, something failed, redisplay form
        return View("UserForm", model);
    }

    // POST: /Admin/DeleteUser/{id}
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteUser(Guid id)
    {
        // Optional: Prevent admin from deleting themselves?
        // var currentUserId = User.FindFirstValue(ClaimTypes.NameIdentifier);
        // if (Guid.TryParse(currentUserId, out var currentGuid) && currentGuid == id)
        // {
        //     TempData["UserError"] = "You cannot delete your own account.";
        //     return RedirectToAction(nameof(Users));
        // }

        var userToDelete = await _userService.GetUserByIdAsync(id); // Get user info for logging/messaging before deleting
        if (userToDelete == null)
        {
             _logger.LogWarning("Admin '{AdminUser}' attempted to delete non-existent user with ID '{UserId}'.", User.Identity?.Name, id);
             TempData["UserError"] = "User not found."; // Use a different key for errors?
             return RedirectToAction(nameof(Users));
        }

        var success = await _userService.DeleteUserAsync(id);

        if (success)
        {
            _logger.LogInformation("Admin '{AdminUser}' deleted user '{DeletedUser}' (ID: {UserId}).", User.Identity?.Name, userToDelete.DisplayName, id);
            TempData["UserMessage"] = $"User '{userToDelete.DisplayName}' deleted successfully.";
            // Note: Associated files in the user's folder are NOT deleted by this action.
            // This might need a separate cleanup mechanism or explicit admin action.
        }
        else
        {
            _logger.LogError("Failed to delete user '{DeletedUser}' (ID: {UserId}) via admin '{AdminUser}'.", userToDelete.DisplayName, id, User.Identity?.Name);
            TempData["UserError"] = $"Failed to delete user '{userToDelete.DisplayName}'.";
        }

        return RedirectToAction(nameof(Users));
    }


    // GET: /Admin/Candidates
    public async Task<IActionResult> Candidates()
    {
        // TODO: Implement candidate listing view
        var candidates = await _candidateService.GetAllCandidatesAsync();
         _logger.LogInformation("Admin accessed Candidate Management page.");
        // Pass candidates to the view
        return View(candidates);
    }

    // GET: /Admin/AddCandidate
    public IActionResult AddCandidate()
    {
        // Initialize required properties
        return View("CandidateForm", new Candidate { Name = string.Empty, CxcRegistrationNo = string.Empty });
    }

    // POST: /Admin/AddCandidate
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> AddCandidate(Candidate model)
    {
        if (ModelState.IsValid)
        {
            var success = await _candidateService.AddCandidateAsync(model);
            if (success)
            {
                _logger.LogInformation("Admin '{AdminUser}' added new candidate '{RegNo}'.", User.Identity?.Name, model.CxcRegistrationNo);
                TempData["CandidateMessage"] = $"Candidate '{model.Name}' ({model.CxcRegistrationNo}) added successfully.";
                return RedirectToAction(nameof(Candidates));
            }
            else
            {
                // Check if the error was a duplicate entry
                var existing = await _candidateService.GetCandidateByRegistrationNoAsync(model.CxcRegistrationNo);
                if (existing != null)
                {
                    ModelState.AddModelError("CxcRegistrationNo", "A candidate with this registration number already exists for the current year.");
                }
                else
                {
                    ModelState.AddModelError(string.Empty, "Failed to add candidate. Please check the details and try again.");
                }
                 _logger.LogWarning("Failed to add candidate '{RegNo}' via admin '{AdminUser}'. Duplicate or save error.", model.CxcRegistrationNo, User.Identity?.Name);
            }
        }
        // If we got this far, something failed
        return View("CandidateForm", model);
    }

    // GET: /Admin/EditCandidate/{id}  (id is CxcRegistrationNo)
    public async Task<IActionResult> EditCandidate(string id)
    {
        var candidate = await _candidateService.GetCandidateByRegistrationNoAsync(id);
        if (candidate == null)
        {
            _logger.LogWarning("Admin '{AdminUser}' attempted to edit non-existent candidate with RegNo '{RegNo}'.", User.Identity?.Name, id);
            return NotFound();
        }
        return View("CandidateForm", candidate);
    }

    // POST: /Admin/EditCandidate/{id} (id is CxcRegistrationNo, but we use the model's value)
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> EditCandidate(string id, Candidate model)
    {
        // Ensure the ID from the route matches the model's registration number
        if (id != model.CxcRegistrationNo)
        {
            return BadRequest("Registration number mismatch.");
        }

        if (ModelState.IsValid)
        {
            var success = await _candidateService.UpdateCandidateAsync(model);
            if (success)
            {
                 _logger.LogInformation("Admin '{AdminUser}' updated candidate '{RegNo}'.", User.Identity?.Name, model.CxcRegistrationNo);
                 TempData["CandidateMessage"] = $"Candidate '{model.Name}' ({model.CxcRegistrationNo}) updated successfully.";
                 return RedirectToAction(nameof(Candidates));
            }
            else
            {
                // Could potentially check if the candidate still exists in case of race condition, but unlikely here.
                ModelState.AddModelError(string.Empty, "Failed to update candidate. Please try again.");
                 _logger.LogError("Failed to update candidate '{RegNo}' via admin '{AdminUser}'.", model.CxcRegistrationNo, User.Identity?.Name);
            }
        }
        // If we got this far, something failed
        return View("CandidateForm", model);
    }

    // POST: /Admin/DeleteCandidate/{id} (id is CxcRegistrationNo)
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteCandidate(string id)
    {
        var candidateToDelete = await _candidateService.GetCandidateByRegistrationNoAsync(id); // Get info for logging/messaging
        if (candidateToDelete == null)
        {
             _logger.LogWarning("Admin '{AdminUser}' attempted to delete non-existent candidate with RegNo '{RegNo}'.", User.Identity?.Name, id);
             TempData["CandidateError"] = "Candidate not found.";
             return RedirectToAction(nameof(Candidates));
        }

        var success = await _candidateService.DeleteCandidateAsync(id);
        if (success)
        {
            _logger.LogInformation("Admin '{AdminUser}' deleted candidate '{CandidateName}' ({RegNo}).", User.Identity?.Name, candidateToDelete.Name, id);
            TempData["CandidateMessage"] = $"Candidate '{candidateToDelete.Name}' ({id}) deleted successfully.";
        }
        else
        {
             _logger.LogError("Failed to delete candidate '{RegNo}' via admin '{AdminUser}'.", id, User.Identity?.Name);
             TempData["CandidateError"] = $"Failed to delete candidate '{candidateToDelete.Name}' ({id}).";
        }
        return RedirectToAction(nameof(Candidates));
    }

    // POST: /Admin/DeleteAllCandidates
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteAllCandidates()
    {
        var success = await _candidateService.DeleteAllCandidatesAsync();
        if (success)
        {
            _logger.LogInformation("Admin '{AdminUser}' deleted all candidates for the current year.", User.Identity?.Name);
            TempData["CandidateMessage"] = "All candidates for the current year deleted successfully.";
        }
        else
        {
            _logger.LogError("Failed to delete all candidates via admin '{AdminUser}'.", User.Identity?.Name);
            TempData["CandidateError"] = "Failed to delete all candidates.";
        }
        return RedirectToAction(nameof(Candidates));
    }


    // POST: /Admin/UploadCandidates
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> UploadCandidates(IFormFile candidateFile)
    {
        if (candidateFile == null || candidateFile.Length == 0)
        {
            TempData["CandidateError"] = "Please select a CSV file to upload.";
            return RedirectToAction(nameof(Candidates));
        }

        // Basic check for CSV extension
        if (Path.GetExtension(candidateFile.FileName).ToLowerInvariant() != ".csv")
        {
             TempData["CandidateError"] = "Invalid file type. Please upload a CSV file.";
             return RedirectToAction(nameof(Candidates));
        }

        try
        {
            using var stream = candidateFile.OpenReadStream();
            var importedCount = await _candidateService.ImportCandidatesFromCsvAsync(stream);

            if (importedCount > 0)
            {
                 _logger.LogInformation("Admin '{AdminUser}' imported {Count} candidates from CSV.", User.Identity?.Name, importedCount);
                 TempData["CandidateMessage"] = $"Successfully imported {importedCount} candidates from '{candidateFile.FileName}'. The previous list was replaced.";
            }
            else
            {
                 _logger.LogWarning("Admin '{AdminUser}' attempted to import candidates from '{FileName}', but 0 valid records were found or an error occurred.", User.Identity?.Name, candidateFile.FileName);
                 TempData["CandidateError"] = $"Could not import valid candidates from '{candidateFile.FileName}'. Please check the file format and content.";
            }
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error during candidate CSV upload by admin '{AdminUser}'.", User.Identity?.Name);
             TempData["CandidateError"] = "An unexpected error occurred during the file upload process.";
        }

        return RedirectToAction(nameof(Candidates));
    }

    // GET: /Admin/DownloadCandidates
    public IActionResult DownloadCandidates()
    {
        var filePath = _candidateService.GetCandidateFilePath();
        if (!System.IO.File.Exists(filePath))
        {
            _logger.LogWarning("Admin '{AdminUser}' attempted to download non-existent candidate file: {FilePath}", User.Identity?.Name, filePath);
            TempData["CandidateError"] = "Candidate file not found or is empty.";
            return RedirectToAction(nameof(Candidates));
        }

        try
        {
            var fileBytes = System.IO.File.ReadAllBytes(filePath);
            var fileName = Path.GetFileName(filePath);
            _logger.LogInformation("Admin '{AdminUser}' downloaded candidate file: {FileName}", User.Identity?.Name, fileName);
            return File(fileBytes, "text/csv", fileName);
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error reading candidate file for download by admin '{AdminUser}': {FilePath}", User.Identity?.Name, filePath);
             TempData["CandidateError"] = "An error occurred while preparing the candidate file for download.";
             return RedirectToAction(nameof(Candidates));
        }
    }


    // GET: /Admin/Subjects
    public async Task<IActionResult> Subjects()
    {
        // TODO: Implement subject listing view
        var subjects = await _subjectService.GetAllSubjectsAsync();
         _logger.LogInformation("Admin accessed Subject Management page.");
        // Pass subjects to the view
        return View(subjects);
    }

    // GET: /Admin/AddSubject
    public IActionResult AddSubject()
    {
        // Initialize required properties, default Level to CSEC
        return View("SubjectForm", new Subject { Name = string.Empty, CxcSubjectCode = string.Empty, Level = SubjectLevel.CSEC });
    }

    // POST: /Admin/AddSubject
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> AddSubject(Subject model)
    {
        if (ModelState.IsValid)
        {
            var success = await _subjectService.AddSubjectAsync(model);
            if (success)
            {
                _logger.LogInformation("Admin '{AdminUser}' added new subject '{Code} - {Name}'.", User.Identity?.Name, model.CxcSubjectCode, model.Name);
                TempData["SubjectMessage"] = $"Subject '{model.Name}' ({model.CxcSubjectCode}) added successfully.";
                return RedirectToAction(nameof(Subjects));
            }
            else
            {
                // Check if the error was a duplicate entry
                var existing = await _subjectService.GetSubjectByCodeAsync(model.CxcSubjectCode);
                if (existing != null)
                {
                    ModelState.AddModelError("CxcSubjectCode", "A subject with this code already exists.");
                }
                else
                {
                    ModelState.AddModelError(string.Empty, "Failed to add subject. Please check the details and try again.");
                }
                 _logger.LogWarning("Failed to add subject '{Code}' via admin '{AdminUser}'. Duplicate or save error.", model.CxcSubjectCode, User.Identity?.Name);
            }
        }
        // If we got this far, something failed
        return View("SubjectForm", model);
    }

    // GET: /Admin/EditSubject/{id} (id is CxcSubjectCode)
    public async Task<IActionResult> EditSubject(string id)
    {
        var subject = await _subjectService.GetSubjectByCodeAsync(id);
        if (subject == null)
        {
            _logger.LogWarning("Admin '{AdminUser}' attempted to edit non-existent subject with Code '{Code}'.", User.Identity?.Name, id);
            return NotFound();
        }
        return View("SubjectForm", subject);
    }

    // POST: /Admin/EditSubject/{id} (id is CxcSubjectCode)
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> EditSubject(string id, Subject model)
    {
        // Ensure the ID from the route matches the model's subject code
        if (id != model.CxcSubjectCode)
        {
            return BadRequest("Subject code mismatch.");
        }

        if (ModelState.IsValid)
        {
            var success = await _subjectService.UpdateSubjectAsync(model);
            if (success)
            {
                 _logger.LogInformation("Admin '{AdminUser}' updated subject '{Code} - {Name}'.", User.Identity?.Name, model.CxcSubjectCode, model.Name);
                 TempData["SubjectMessage"] = $"Subject '{model.Name}' ({model.CxcSubjectCode}) updated successfully.";
                 return RedirectToAction(nameof(Subjects));
            }
            else
            {
                ModelState.AddModelError(string.Empty, "Failed to update subject. Please try again.");
                 _logger.LogError("Failed to update subject '{Code}' via admin '{AdminUser}'.", model.CxcSubjectCode, User.Identity?.Name);
            }
        }
        // If we got this far, something failed
        return View("SubjectForm", model);
    }

    // POST: /Admin/DeleteSubject/{id} (id is CxcSubjectCode)
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteSubject(string id)
    {
        var subjectToDelete = await _subjectService.GetSubjectByCodeAsync(id); // Get info for logging/messaging
        if (subjectToDelete == null)
        {
             _logger.LogWarning("Admin '{AdminUser}' attempted to delete non-existent subject with Code '{Code}'.", User.Identity?.Name, id);
             TempData["SubjectError"] = "Subject not found.";
             return RedirectToAction(nameof(Subjects));
        }

        var success = await _subjectService.DeleteSubjectAsync(id);
        if (success)
        {
            _logger.LogInformation("Admin '{AdminUser}' deleted subject '{SubjectName}' ({Code}).", User.Identity?.Name, subjectToDelete.Name, id);
            TempData["SubjectMessage"] = $"Subject '{subjectToDelete.Name}' ({id}) deleted successfully.";
        }
        else
        {
             _logger.LogError("Failed to delete subject '{Code}' via admin '{AdminUser}'.", id, User.Identity?.Name);
             TempData["SubjectError"] = $"Failed to delete subject '{subjectToDelete.Name}' ({id}).";
        }
        return RedirectToAction(nameof(Subjects));
    }

    // POST: /Admin/DeleteAllSubjects
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteAllSubjects()
    {
        var success = await _subjectService.DeleteAllSubjectsAsync();
        if (success)
        {
            _logger.LogInformation("Admin '{AdminUser}' deleted all subjects.", User.Identity?.Name);
            TempData["SubjectMessage"] = "All subjects deleted successfully.";
        }
        else
        {
            _logger.LogError("Failed to delete all subjects via admin '{AdminUser}'.", User.Identity?.Name);
            TempData["SubjectError"] = "Failed to delete all subjects.";
        }
        return RedirectToAction(nameof(Subjects));
    }


    // POST: /Admin/UploadSubjects
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> UploadSubjects(IFormFile subjectFile)
    {
        if (subjectFile == null || subjectFile.Length == 0)
        {
            TempData["SubjectError"] = "Please select a CSV file to upload.";
            return RedirectToAction(nameof(Subjects));
        }

        if (Path.GetExtension(subjectFile.FileName).ToLowerInvariant() != ".csv")
        {
             TempData["SubjectError"] = "Invalid file type. Please upload a CSV file.";
             return RedirectToAction(nameof(Subjects));
        }

        try
        {
            using var stream = subjectFile.OpenReadStream();
            var importedCount = await _subjectService.ImportSubjectsFromCsvAsync(stream);

            if (importedCount > 0)
            {
                 _logger.LogInformation("Admin '{AdminUser}' imported {Count} subjects from CSV.", User.Identity?.Name, importedCount);
                 TempData["SubjectMessage"] = $"Successfully imported {importedCount} subjects from '{subjectFile.FileName}'. The previous list was replaced.";
            }
            else
            {
                 _logger.LogWarning("Admin '{AdminUser}' attempted to import subjects from '{FileName}', but 0 valid records were found or an error occurred.", User.Identity?.Name, subjectFile.FileName);
                 TempData["SubjectError"] = $"Could not import valid subjects from '{subjectFile.FileName}'. Please check the file format and content.";
            }
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error during subject CSV upload by admin '{AdminUser}'.", User.Identity?.Name);
             TempData["SubjectError"] = "An unexpected error occurred during the file upload process.";
        }

        return RedirectToAction(nameof(Subjects));
    }

    // GET: /Admin/DownloadSubjects
    public IActionResult DownloadSubjects()
    {
        var filePath = _subjectService.GetSubjectFilePath();
        if (!System.IO.File.Exists(filePath))
        {
            _logger.LogWarning("Admin '{AdminUser}' attempted to download non-existent subject file: {FilePath}", User.Identity?.Name, filePath);
            TempData["SubjectError"] = "Subject file not found or is empty.";
            return RedirectToAction(nameof(Subjects));
        }

        try
        {
            var fileBytes = System.IO.File.ReadAllBytes(filePath);
            var fileName = Path.GetFileName(filePath); // Should be "subjects.csv"
            _logger.LogInformation("Admin '{AdminUser}' downloaded subject file: {FileName}", User.Identity?.Name, fileName);
            return File(fileBytes, "text/csv", fileName);
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error reading subject file for download by admin '{AdminUser}': {FilePath}", User.Identity?.Name, filePath);
             TempData["SubjectError"] = "An error occurred while preparing the subject file for download.";
             return RedirectToAction(nameof(Subjects));
         }
    }

    // POST: /Admin/ImportDefaultSubjects
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> ImportDefaultSubjects([FromServices] IWebHostEnvironment env, [FromServices] IStorageService storageService) // Inject env and storage service
    {
        // Path to the default subjects file included with the application (in ContentRoot)
        var localDefaultSubjectPath = Path.Combine(env.ContentRootPath, "Data2", "subjects.csv");
        // Relative path in storage
        var storageSubjectPath = _subjectService.GetSubjectFilePath(); // e.g., "Data2/subjects.csv"

        if (!System.IO.File.Exists(localDefaultSubjectPath))
        {
             _logger.LogError("Default subjects file not found at expected location: {Path}", localDefaultSubjectPath);
             TempData["SubjectError"] = "Default subjects file is missing from the application deployment.";
             return RedirectToAction(nameof(Subjects));
        }

        try
        {
            // Use the storage service to copy the local file to the configured storage (Azure or Local)
            bool success = await storageService.CopyLocalFileToStorageAsync(localDefaultSubjectPath, storageSubjectPath, overwrite: true);

            if (success)
            {
                 _logger.LogInformation("Admin '{AdminUser}' imported default subjects from local file to storage path {StoragePath}.", User.Identity?.Name, storageSubjectPath);
                 TempData["SubjectMessage"] = "Default subjects imported successfully, overwriting previous list in storage.";
                 // Optional: Force reload of SubjectService cache if it exists? Currently, SubjectService reads on demand.
            }
            else
            {
                 _logger.LogError("Failed to copy default subjects file from {LocalPath} to storage path {StoragePath} via admin '{AdminUser}'.", localDefaultSubjectPath, storageSubjectPath, User.Identity?.Name);
                 TempData["SubjectError"] = "Failed to import default subjects into storage.";
            }
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error during default subjects import by admin '{AdminUser}'.", User.Identity?.Name);
             TempData["SubjectError"] = "An unexpected error occurred during the default subjects import.";
        }

        return RedirectToAction(nameof(Subjects));
    }


    // GET: /Admin/Files
    public async Task<IActionResult> Files()
    {
        // TODO: Implement file listing view (all files from all user folders)
        var allFiles = await _fileService.GetAllFilesInDataDirectoryAsync();
         _logger.LogInformation("Admin accessed File Management page.");
        // Pass file list to the view
        return View(allFiles); // Placeholder - View needs to be created
    }

    // POST: /Admin/DeleteFile
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> DeleteFile(string userFolder, string fileName)
    {
        if (string.IsNullOrEmpty(userFolder) || string.IsNullOrEmpty(fileName))
        {
            TempData["FileError"] = "Invalid folder or file name provided.";
            return RedirectToAction(nameof(Files));
        }

        // Note: The FileService handles logging internally
        var success = await _fileService.DeleteSpecificFileAsync(userFolder, fileName);

        if (success)
        {
            TempData["FileMessage"] = $"File '{fileName}' from folder '{userFolder}' deleted successfully.";
        }
        else
        {
            TempData["FileError"] = $"Failed to delete file '{fileName}' from folder '{userFolder}'.";
        }
        return RedirectToAction(nameof(Files));
    }

    // GET: /Admin/DownloadFile
    public async Task<IActionResult> DownloadFile(string userFolder, string fileName)
    {
         if (string.IsNullOrEmpty(userFolder) || string.IsNullOrEmpty(fileName))
        {
            TempData["FileError"] = "Invalid folder or file name provided.";
            return RedirectToAction(nameof(Files));
        }

        var fileResult = await _fileService.GetFileDownloadAsync(userFolder, fileName);

        if (fileResult == null)
        {
            TempData["FileError"] = $"File '{fileName}' not found in folder '{userFolder}' or could not be prepared for download.";
            return RedirectToAction(nameof(Files));
        }

        return fileResult;
    }
}

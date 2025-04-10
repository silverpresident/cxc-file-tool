using Microsoft.AspNetCore.Mvc;
using System.IO.Compression; // Required for ZipFile
using MimeTypes; // Reverting to MimeTypes namespace for MimeTypeMapOfficial package

namespace cxc_tool_asp.Services;

/// <summary>
/// Service implementation for managing file operations within user-specific folders in the 'Data' directory.
/// </summary>
public class FileService : IFileService
{
    private readonly string _dataDirectoryPath;
    private readonly ILogger<FileService> _logger;

    public FileService(IWebHostEnvironment env, ILogger<FileService> logger)
    {
        // All user files are stored in subdirectories within the 'Data' folder
        _dataDirectoryPath = Path.Combine(env.ContentRootPath, "Data");
        Directory.CreateDirectory(_dataDirectoryPath); // Ensure base Data directory exists
        _logger = logger;
    }

    private string GetUserFolderPath(string userFolderName)
    {
        // Basic sanitization to prevent path traversal
        var sanitizedFolderName = Path.GetFileName(userFolderName);
        if (string.IsNullOrWhiteSpace(sanitizedFolderName) || sanitizedFolderName.Contains(".."))
        {
            throw new ArgumentException("Invalid user folder name.", nameof(userFolderName));
        }
        return Path.Combine(_dataDirectoryPath, sanitizedFolderName);
    }

    public async Task<string?> SaveFileAsync(string userFolderName, IFormFile file, string? desiredFileName = null)
    {
        if (file == null || file.Length == 0)
        {
            _logger.LogWarning("Attempted to save a null or empty file for user folder {UserFolder}.", userFolderName);
            return null;
        }

        try
        {
            var userFolderPath = GetUserFolderPath(userFolderName);
            Directory.CreateDirectory(userFolderPath); // Ensure user's folder exists

            var originalFileName = Path.GetFileName(file.FileName); // Sanitize original filename
            var fileExtension = Path.GetExtension(originalFileName);
            var finalFileName = string.IsNullOrWhiteSpace(desiredFileName)
                ? originalFileName
                : Path.ChangeExtension(desiredFileName, fileExtension);

            // Basic sanitization for the final file name
            finalFileName = Path.GetFileName(finalFileName);
             if (string.IsNullOrWhiteSpace(finalFileName) || finalFileName.Contains("..") || finalFileName.IndexOfAny(Path.GetInvalidFileNameChars()) >= 0)
            {
                _logger.LogError("Invalid final file name generated or provided: {FileName}", finalFileName);
                return null;
            }


            var filePath = Path.Combine(userFolderPath, finalFileName);

            // Prevent overwriting? For now, it overwrites. Add check if needed.
            // if (File.Exists(filePath)) { ... handle overwrite logic ... }

            await using var stream = new FileStream(filePath, FileMode.Create);
            await file.CopyToAsync(stream);

            _logger.LogInformation("Successfully saved file '{FileName}' to user folder '{UserFolder}'.", finalFileName, userFolderName);
            // Return path relative to Data directory? Or full path? Let's return relative for potential web access if needed later.
            // For now, just returning the final filename might be sufficient for listing. Let's return the filename.
            return finalFileName;
        }
        catch (ArgumentException argEx)
        {
             _logger.LogError(argEx, "Invalid argument provided during file save for user folder {UserFolder}.", userFolderName);
             return null;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error saving file '{OriginalFileName}' for user folder {UserFolder}.", file.FileName, userFolderName);
            return null;
        }
    }

    public Task<List<string>> GetFilesAsync(string userFolderName)
    {
        try
        {
            var userFolderPath = GetUserFolderPath(userFolderName);
            if (!Directory.Exists(userFolderPath))
            {
                _logger.LogWarning("Attempted to list files for non-existent user folder: {UserFolder}", userFolderName);
                return Task.FromResult(new List<string>()); // Return empty list
            }

            var files = Directory.GetFiles(userFolderPath)
                               .Select(Path.GetFileName)
                               .Where(f => f != null) // Ensure GetFileName didn't return null
                               .ToList();
            return Task.FromResult(files!); // Nullable context satisfied by Where filter
        }
        catch (ArgumentException argEx)
        {
             _logger.LogError(argEx, "Invalid argument provided when listing files for user folder {UserFolder}.", userFolderName);
             return Task.FromResult(new List<string>());
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error listing files for user folder {UserFolder}.", userFolderName);
            return Task.FromResult(new List<string>()); // Return empty list on error
        }
    }

     public string? GetFilePath(string userFolderName, string fileName)
    {
        try
        {
            var userFolderPath = GetUserFolderPath(userFolderName);
            var sanitizedFileName = Path.GetFileName(fileName); // Sanitize

            if (string.IsNullOrWhiteSpace(sanitizedFileName) || sanitizedFileName.Contains(".."))
            {
                 _logger.LogWarning("Attempt to access invalid file path: User={UserFolder}, File={FileName}", userFolderName, fileName);
                 return null;
            }

            var filePath = Path.Combine(userFolderPath, sanitizedFileName);

            return File.Exists(filePath) ? filePath : null;
        }
        catch (ArgumentException argEx)
        {
             _logger.LogError(argEx, "Invalid argument provided when getting file path for User={UserFolder}, File={FileName}", userFolderName, fileName);
             return null;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error getting file path for User={UserFolder}, File={FileName}", userFolderName, fileName);
            return null;
        }
    }


    public Task<bool> DeleteFileAsync(string userFolderName, string fileName)
    {
        try
        {
            var filePath = GetFilePath(userFolderName, fileName);

            if (filePath != null && File.Exists(filePath))
            {
                File.Delete(filePath);
                _logger.LogInformation("Deleted file '{FileName}' from user folder '{UserFolder}'.", fileName, userFolderName);
            }
            else
            {
                 _logger.LogWarning("Attempted to delete non-existent file '{FileName}' from user folder '{UserFolder}'.", fileName, userFolderName);
            }
            return Task.FromResult(true); // Return true even if file didn't exist
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error deleting file '{FileName}' from user folder {UserFolder}.", fileName, userFolderName);
            return Task.FromResult(false);
        }
    }

    public Task<string?> RenameFileAsync(string userFolderName, string originalFileName, string registrationNo, string subjectCode, string docIdentifier)
    {
        try
        {
            var userFolderPath = GetUserFolderPath(userFolderName);
            var sanitizedOriginalFileName = Path.GetFileName(originalFileName);

            var sourceFilePath = Path.Combine(userFolderPath, sanitizedOriginalFileName);

            if (!File.Exists(sourceFilePath))
            {
                _logger.LogError("Original file not found for renaming: {OriginalFileName} in folder {UserFolder}", sanitizedOriginalFileName, userFolderName);
                return Task.FromResult<string?>(null);
            }

            var fileExtension = Path.GetExtension(sanitizedOriginalFileName); // Includes the dot (.)
            var newFileName = $"{registrationNo}{subjectCode}{docIdentifier}{fileExtension}";

            // Basic validation of components (Updated SubjectCode regex to 8 digits)
            if (!System.Text.RegularExpressions.Regex.IsMatch(registrationNo, @"^\d{10}$") ||
                !System.Text.RegularExpressions.Regex.IsMatch(subjectCode, @"^\d{8}$") ||
                string.IsNullOrWhiteSpace(docIdentifier))
            {
                 _logger.LogError("Invalid components provided for renaming file {OriginalFileName}: Reg={RegNo}, Subj={SubjCode}, ID={DocId}",
                    sanitizedOriginalFileName, registrationNo, subjectCode, docIdentifier);
                 return Task.FromResult<string?>(null);
            }

            var destinationFilePath = Path.Combine(userFolderPath, newFileName);

            // Handle potential overwrite? If new name already exists, fail for now.
            if (File.Exists(destinationFilePath))
            {
                _logger.LogWarning("Rename failed: Destination file already exists: {NewFileName} in folder {UserFolder}", newFileName, userFolderName);
                return Task.FromResult<string?>(null);
            }

            File.Move(sourceFilePath, destinationFilePath);
            _logger.LogInformation("Renamed file '{OriginalFileName}' to '{NewFileName}' in folder {UserFolder}", sanitizedOriginalFileName, newFileName, userFolderName);
            return Task.FromResult<string?>(newFileName);
        }
         catch (ArgumentException argEx)
        {
             _logger.LogError(argEx, "Invalid argument provided during file rename for user folder {UserFolder}.", userFolderName);
             return Task.FromResult<string?>(null);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error renaming file '{OriginalFileName}' in folder {UserFolder}.", originalFileName, userFolderName);
            return Task.FromResult<string?>(null);
        }
    }

    public Task<bool> DeleteUserFilesAsync(string userFolderName)
    {
        try
        {
            var userFolderPath = GetUserFolderPath(userFolderName);
            if (Directory.Exists(userFolderPath))
            {
                Directory.Delete(userFolderPath, true); // Delete folder and all contents
                _logger.LogInformation("Deleted user folder and all contents: {UserFolder}", userFolderName);
            }
             else
            {
                 _logger.LogWarning("Attempted to delete non-existent user folder: {UserFolder}", userFolderName);
            }
            return Task.FromResult(true);
        }
         catch (ArgumentException argEx)
        {
             _logger.LogError(argEx, "Invalid argument provided when deleting user folder {UserFolder}.", userFolderName);
             return Task.FromResult(false);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error deleting user folder {UserFolder}.", userFolderName);
            return Task.FromResult(false);
        }
    }

    public Task<List<(string UserFolder, string FileName)>> GetAllFilesInDataDirectoryAsync()
    {
        var allFiles = new List<(string UserFolder, string FileName)>();
        try
        {
            var subDirectories = Directory.GetDirectories(_dataDirectoryPath);
            foreach (var dirPath in subDirectories)
            {
                var userFolder = Path.GetFileName(dirPath);
                var files = Directory.GetFiles(dirPath);
                foreach (var filePath in files)
                {
                    allFiles.Add((userFolder, Path.GetFileName(filePath)));
                }
            }
             _logger.LogInformation("Retrieved {Count} files across all user folders in {DataDir}", allFiles.Count, _dataDirectoryPath);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error retrieving all files from data directory {DataDir}", _dataDirectoryPath);
            // Return empty list or re-throw depending on desired behavior
        }
        return Task.FromResult(allFiles);
    }

     public async Task<bool> DeleteSpecificFileAsync(string userFolderName, string fileName)
    {
        // This reuses the existing DeleteFileAsync logic
        return await DeleteFileAsync(userFolderName, fileName);
    }

    public Task<FileStreamResult?> GetFileDownloadAsync(string userFolderName, string fileName)
    {
        try
        {
            var filePath = GetFilePath(userFolderName, fileName);
            if (filePath == null || !File.Exists(filePath))
            {
                _logger.LogWarning("Attempted to download non-existent file: User={UserFolder}, File={FileName}", userFolderName, fileName);
                return Task.FromResult<FileStreamResult?>(null);
            }

            var memoryStream = new MemoryStream();
            using (var stream = new FileStream(filePath, FileMode.Open, FileAccess.Read))
            {
                stream.CopyTo(memoryStream);
            }
            memoryStream.Position = 0; // Reset stream position

            // Determine MIME type
            var mimeType = MimeTypeMap.GetMimeType(Path.GetExtension(fileName));

            var result = new FileStreamResult(memoryStream, mimeType)
            {
                FileDownloadName = fileName // The name the user sees when downloading
            };

            _logger.LogInformation("Prepared file for download: User={UserFolder}, File={FileName}", userFolderName, fileName);
            return Task.FromResult<FileStreamResult?>(result);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error preparing file for download: User={UserFolder}, File={FileName}", userFolderName, fileName);
            return Task.FromResult<FileStreamResult?>(null);
        }
    }

     public async Task<MemoryStream?> CreateZipArchiveAsync(string userFolderName, List<string> filesToZip, string zipFileName)
    {
        var memoryStream = new MemoryStream();
        try
        {
            using (var archive = new ZipArchive(memoryStream, ZipArchiveMode.Create, true)) // Leave stream open
            {
                foreach (var fileName in filesToZip)
                {
                    var filePath = GetFilePath(userFolderName, fileName);
                    if (filePath != null && File.Exists(filePath))
                    {
                        // Use the filename as the entry name in the zip
                        archive.CreateEntryFromFile(filePath, fileName);
                    }
                    else
                    {
                        _logger.LogWarning("File not found while creating zip archive: User={UserFolder}, File={FileName}", userFolderName, fileName);
                        // Optionally skip or throw error
                    }
                }
            } // Archive is disposed here, but memoryStream remains open

            memoryStream.Position = 0; // Reset stream position for reading
            _logger.LogInformation("Created zip archive '{ZipFileName}.zip' for user {UserFolder} with {Count} files.", zipFileName, userFolderName, filesToZip.Count);
            return memoryStream;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error creating zip archive '{ZipFileName}.zip' for user {UserFolder}.", zipFileName, userFolderName);
            await memoryStream.DisposeAsync(); // Dispose the stream on error
            return null;
        }
    }
}

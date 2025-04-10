using Microsoft.AspNetCore.Http; // For IFormFile
using Microsoft.AspNetCore.Mvc; // For FileStreamResult
using Microsoft.Extensions.Logging;
using System.Collections.Generic;
using System.IO;
using System.IO.Compression; // For ZipArchive
using System.Linq;
using System.Threading.Tasks;
using MimeTypes; // For MimeTypeMap
using Microsoft.Net.Http.Headers; // For ContentDispositionHeaderValue

namespace cxc_tool_asp.Services;

/// <summary>
/// Service implementation for managing file operations using IStorageService.
/// Acts as a layer between controllers and the storage abstraction.
/// </summary>
public class FileService : IFileService // Keep implementing the original interface for now
{
    private readonly IStorageService _storageService;
    private readonly ILogger<FileService> _logger;
    private readonly string _userDataRelativePath = "Data"; // Base relative path for user folders

    public FileService(IStorageService storageService, ILogger<FileService> logger)
    {
        _storageService = storageService;
        _logger = logger;
    }

    // Helper to construct the relative path for a user's file
    private string GetUserFileRelativePath(string userFolderName, string fileName)
    {
        // Basic sanitization
        var sanitizedFolderName = Path.GetFileName(userFolderName);
        var sanitizedFileName = Path.GetFileName(fileName);

        if (string.IsNullOrWhiteSpace(sanitizedFolderName) || sanitizedFolderName.Contains("..") ||
            string.IsNullOrWhiteSpace(sanitizedFileName) || sanitizedFileName.Contains(".."))
        {
            throw new ArgumentException("Invalid user folder name or file name.");
        }
        // Path format: Data/{FolderName}/{FileName}
        return Path.Combine(_userDataRelativePath, sanitizedFolderName, sanitizedFileName).Replace(Path.DirectorySeparatorChar, '/');
    }

     // Helper to construct the relative path for a user's folder
    private string GetUserFolderRelativePath(string userFolderName)
    {
        var sanitizedFolderName = Path.GetFileName(userFolderName);
        if (string.IsNullOrWhiteSpace(sanitizedFolderName) || sanitizedFolderName.Contains(".."))
        {
            throw new ArgumentException("Invalid user folder name.");
        }
         // Path format: Data/{FolderName}
        return Path.Combine(_userDataRelativePath, sanitizedFolderName).Replace(Path.DirectorySeparatorChar, '/');
    }


    public async Task<string?> SaveFileAsync(string userFolderName, IFormFile file, string? desiredFileName = null)
    {
        if (file == null || file.Length == 0) return null;

        try
        {
            var originalFileName = Path.GetFileName(file.FileName); // Sanitize
            var fileExtension = Path.GetExtension(originalFileName);
            var baseName = string.IsNullOrWhiteSpace(desiredFileName)
                ? Path.GetFileNameWithoutExtension(originalFileName)
                : desiredFileName;

            // Sanitize final base name
             baseName = Path.GetInvalidFileNameChars().Aggregate(baseName, (current, c) => current.Replace(c.ToString(), string.Empty));
             if (string.IsNullOrWhiteSpace(baseName)) baseName = "uploaded_file"; // Fallback name

            var finalFileName = baseName + fileExtension;
            var relativePath = GetUserFileRelativePath(userFolderName, finalFileName);

            bool success = await _storageService.SaveFileAsync(relativePath, file);

            if (success)
            {
                _logger.LogInformation("Saved file '{FileName}' for user folder '{UserFolder}' via storage service.", finalFileName, userFolderName);
                return finalFileName; // Return just the filename
            }
            else
            {
                 _logger.LogError("Storage service failed to save file '{FileName}' for user folder '{UserFolder}'.", finalFileName, userFolderName);
                 return null;
            }
        }
        catch (ArgumentException argEx)
        {
             _logger.LogError(argEx, "Invalid argument constructing path for SaveFileAsync. User: {UserFolder}, File: {FileName}", userFolderName, file.FileName);
             return null;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error in SaveFileAsync. User: {UserFolder}, File: {FileName}", userFolderName, file.FileName);
            return null;
        }
    }

    public async Task<List<string>> GetFilesAsync(string userFolderName)
    {
        try
        {
            var relativeDirectoryPath = GetUserFolderRelativePath(userFolderName);
            return await _storageService.ListFilesAsync(relativeDirectoryPath);
        }
        catch (ArgumentException argEx)
        {
             _logger.LogError(argEx, "Invalid argument constructing path for GetFilesAsync. User: {UserFolder}", userFolderName);
             return new List<string>();
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error in GetFilesAsync for user folder {UserFolder}", userFolderName);
             return new List<string>();
        }
    }

    public string? GetFilePath(string userFolderName, string fileName)
    {
        // This method might be less relevant now. It originally returned a local path.
        // We could return the relative path, or null if the concept doesn't fit.
        // Let's return the relative path for potential use, but it's not a local system path.
         try
        {
            return GetUserFileRelativePath(userFolderName, fileName);
        }
         catch (ArgumentException argEx)
        {
             _logger.LogError(argEx, "Invalid argument constructing path for GetFilePath. User: {UserFolder}, File: {FileName}", userFolderName, fileName);
             return null;
        }
    }

    public async Task<bool> DeleteFileAsync(string userFolderName, string fileName)
    {
         try
        {
            var relativePath = GetUserFileRelativePath(userFolderName, fileName);
            return await _storageService.DeleteFileAsync(relativePath);
        }
         catch (ArgumentException argEx)
        {
             _logger.LogError(argEx, "Invalid argument constructing path for DeleteFileAsync. User: {UserFolder}, File: {FileName}", userFolderName, fileName);
             return false;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error in DeleteFileAsync. User: {UserFolder}, File: {FileName}", userFolderName, fileName);
            return false;
        }
    }

    public async Task<string?> RenameFileAsync(string userFolderName, string originalFileName, string registrationNo, string subjectCode, string docIdentifier)
    {
        try
        {
            var sourceRelativePath = GetUserFileRelativePath(userFolderName, originalFileName);

            // Construct new name (validation happens in controller now, but double check?)
            var fileExtension = Path.GetExtension(originalFileName);
            var newFileName = $"{registrationNo}{subjectCode}{docIdentifier}{fileExtension}";
            var destinationRelativePath = GetUserFileRelativePath(userFolderName, newFileName);

            bool success = await _storageService.MoveFileAsync(sourceRelativePath, destinationRelativePath);

            if (success)
            {
                 _logger.LogInformation("Renamed file '{OriginalFileName}' to '{NewFileName}' for user {UserFolder} via storage service.", originalFileName, newFileName, userFolderName);
                 return newFileName;
            }
            else
            {
                 _logger.LogWarning("Storage service failed to move/rename file from '{Source}' to '{Destination}'.", sourceRelativePath, destinationRelativePath);
                 return null;
            }
        }
        catch (ArgumentException argEx)
        {
             _logger.LogError(argEx, "Invalid argument constructing path for RenameFileAsync. User: {UserFolder}, File: {OriginalFileName}", userFolderName, originalFileName);
             return null;
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error in RenameFileAsync. User: {UserFolder}, File: {OriginalFileName}", userFolderName, originalFileName);
             return null;
        }
    }

    public async Task<bool> DeleteUserFilesAsync(string userFolderName)
    {
        // This now means deleting the "directory" in storage
         try
        {
            var relativeDirectoryPath = GetUserFolderRelativePath(userFolderName);
            return await _storageService.DeleteDirectoryAsync(relativeDirectoryPath);
        }
         catch (ArgumentException argEx)
        {
             _logger.LogError(argEx, "Invalid argument constructing path for DeleteUserFilesAsync. User: {UserFolder}", userFolderName);
             return false;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error in DeleteUserFilesAsync for user folder {UserFolder}", userFolderName);
            return false;
        }
    }

     public async Task<List<(string UserFolder, string FileName)>> GetAllFilesInDataDirectoryAsync()
     {
        // This requires listing subdirectories and then listing files in each.
        var allFiles = new List<(string UserFolder, string FileName)>();
        try
        {
            var userFolders = await _storageService.ListSubdirectoriesAsync(_userDataRelativePath);
            foreach (var userFolder in userFolders)
            {
                var relativeFolderPath = GetUserFolderRelativePath(userFolder);
                var filesInFolder = await _storageService.ListFilesAsync(relativeFolderPath);
                allFiles.AddRange(filesInFolder.Select(f => (userFolder, f)));
            }
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error retrieving all files from storage directory {DataPath}", _userDataRelativePath);
        }
        return allFiles;
     }

     public async Task<bool> DeleteSpecificFileAsync(string userFolderName, string fileName)
    {
        // Reuses DeleteFileAsync
        return await DeleteFileAsync(userFolderName, fileName);
    }

    public async Task<FileStreamResult?> GetFileDownloadAsync(string userFolderName, string fileName)
    {
        try
        {
            var relativePath = GetUserFileRelativePath(userFolderName, fileName);
            var stream = await _storageService.ReadFileAsStreamAsync(relativePath);

            if (stream == null)
            {
                 _logger.LogWarning("File not found in storage for download: {RelativePath}", relativePath);
                return null;
            }

            var contentType = MimeTypeMap.GetMimeType(Path.GetExtension(fileName));

            // Important: For Blob streams, they might not be seekable for direct use in FileStreamResult
            // depending on how they were downloaded. Copying to MemoryStream is safer.
            var memoryStream = new MemoryStream();
            await stream.CopyToAsync(memoryStream);
            await stream.DisposeAsync(); // Dispose the original stream
            memoryStream.Position = 0;

            return new FileStreamResult(memoryStream, contentType)
            {
                FileDownloadName = fileName
            };
        }
         catch (ArgumentException argEx)
        {
             _logger.LogError(argEx, "Invalid argument constructing path for GetFileDownloadAsync. User: {UserFolder}, File: {FileName}", userFolderName, fileName);
             return null;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error preparing file for download: {UserFolder}/{FileName}", userFolderName, fileName);
            return null;
        }
    }

     public async Task<MemoryStream?> CreateZipArchiveAsync(string userFolderName, List<string> filesToZip, string zipFileName)
    {
        var memoryStream = new MemoryStream();
        try
        {
            using (var archive = new ZipArchive(memoryStream, ZipArchiveMode.Create, true))
            {
                foreach (var fileName in filesToZip)
                {
                    var relativePath = GetUserFileRelativePath(userFolderName, fileName);
                    var fileStream = await _storageService.ReadFileAsStreamAsync(relativePath);

                    if (fileStream != null)
                    {
                        var entry = archive.CreateEntry(fileName, CompressionLevel.Optimal);
                        await using (var entryStream = entry.Open())
                        {
                            await fileStream.CopyToAsync(entryStream);
                        }
                        await fileStream.DisposeAsync(); // Dispose stream after copying
                    }
                    else
                    {
                        _logger.LogWarning("File not found in storage while creating zip archive: {RelativePath}", relativePath);
                    }
                }
            }
            memoryStream.Position = 0;
            return memoryStream;
        }
         catch (ArgumentException argEx)
        {
             _logger.LogError(argEx, "Invalid argument constructing path during zip creation. User: {UserFolder}", userFolderName);
             await memoryStream.DisposeAsync();
             return null;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error creating zip archive '{ZipFileName}.zip' for user {UserFolder}.", zipFileName, userFolderName);
            await memoryStream.DisposeAsync();
            return null;
        }
    }
}

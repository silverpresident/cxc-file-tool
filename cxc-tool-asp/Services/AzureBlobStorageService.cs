using Azure; // Required for Response<T>
using Azure.Storage.Blobs;
using Azure.Storage.Blobs.Models;
using Microsoft.AspNetCore.Http; // For IFormFile
using Microsoft.Extensions.Configuration; // For IConfiguration
using Microsoft.Extensions.Logging;
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading.Tasks;

namespace cxc_tool_asp.Services;

/// <summary>
/// Implements IStorageService using Azure Blob Storage.
/// </summary>
public class AzureBlobStorageService : IStorageService
{
    private readonly BlobServiceClient _blobServiceClient;
    private readonly string _dataContainerName;
    private readonly string _data2ContainerName;
    private readonly ILogger<AzureBlobStorageService> _logger;

    // Cache container clients
    private BlobContainerClient? _dataContainerClient;
    private BlobContainerClient? _data2ContainerClient;

    public AzureBlobStorageService(IConfiguration configuration, ILogger<AzureBlobStorageService> logger)
    {
        _logger = logger;
        var connectionString = configuration["StorageSettings:AzureBlobConnectionString"];
        _dataContainerName = configuration["StorageSettings:DataContainerName"] ?? "data"; // Default names
        _data2ContainerName = configuration["StorageSettings:Data2ContainerName"] ?? "data2";

        if (string.IsNullOrEmpty(connectionString))
        {
            _logger.LogError("Azure Blob Storage connection string is missing in configuration.");
            throw new InvalidOperationException("Azure Blob Storage connection string is not configured.");
        }

        try
        {
            _blobServiceClient = new BlobServiceClient(connectionString);
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Failed to initialize Azure BlobServiceClient.");
             throw;
        }
    }

    // Helper to get the correct container client based on the path prefix
    private async Task<BlobContainerClient?> GetContainerClientAsync(string relativePath)
    {
        string containerName;
        if (relativePath.StartsWith("Data2/", StringComparison.OrdinalIgnoreCase) || relativePath.Equals("Data2", StringComparison.OrdinalIgnoreCase))
        {
            containerName = _data2ContainerName;
            if (_data2ContainerClient == null)
            {
                _data2ContainerClient = _blobServiceClient.GetBlobContainerClient(containerName);
                await _data2ContainerClient.CreateIfNotExistsAsync(PublicAccessType.None); // Ensure container exists (private)
            }
            return _data2ContainerClient;
        }
        else if (relativePath.StartsWith("Data/", StringComparison.OrdinalIgnoreCase) || relativePath.Equals("Data", StringComparison.OrdinalIgnoreCase))
        {
             containerName = _dataContainerName;
             if (_dataContainerClient == null)
             {
                _dataContainerClient = _blobServiceClient.GetBlobContainerClient(containerName);
                await _dataContainerClient.CreateIfNotExistsAsync(PublicAccessType.None); // Ensure container exists (private)
             }
             return _dataContainerClient;
        }
        else
        {
             _logger.LogError("Invalid relative path prefix for blob storage: {RelativePath}. Must start with 'Data/' or 'Data2/'.", relativePath);
             return null; // Or throw? For now, return null and let caller handle.
        }
    }

    // Helper to get the blob path (remove container prefix)
    private string GetBlobPath(string relativePath)
    {
        if (relativePath.StartsWith("Data2/", StringComparison.OrdinalIgnoreCase))
        {
            return relativePath.Substring("Data2/".Length);
        }
        if (relativePath.StartsWith("Data/", StringComparison.OrdinalIgnoreCase))
        {
            return relativePath.Substring("Data/".Length);
        }
        // If it's just "Data" or "Data2", treat it as root (empty blob path)
        if (relativePath.Equals("Data", StringComparison.OrdinalIgnoreCase) || relativePath.Equals("Data2", StringComparison.OrdinalIgnoreCase))
        {
            return string.Empty;
        }
        // Should not happen if GetContainerClientAsync worked, but as fallback:
        return relativePath;
    }


    public async Task<bool> SaveFileAsync(string relativePath, Stream stream)
    {
        try
        {
            var containerClient = await GetContainerClientAsync(relativePath);
            if (containerClient == null) return false;

            var blobPath = GetBlobPath(relativePath);
            if (string.IsNullOrEmpty(blobPath)) {
                 _logger.LogError("Cannot save file to container root: {RelativePath}", relativePath);
                 return false;
            }

            BlobClient blobClient = containerClient.GetBlobClient(blobPath);
            stream.Position = 0; // Ensure stream is at the beginning
            await blobClient.UploadAsync(stream, overwrite: true); // Overwrite if exists
            _logger.LogDebug("Saved stream to blob: {Container}/{BlobPath}", containerClient.Name, blobPath);
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error saving stream to blob: {RelativePath}", relativePath);
            return false;
        }
    }

    public async Task<bool> SaveFileAsync(string relativePath, IFormFile formFile)
    {
        try
        {
            await using var stream = formFile.OpenReadStream();
            return await SaveFileAsync(relativePath, stream); // Reuse stream saving logic
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error saving IFormFile '{FileName}' to blob: {RelativePath}", formFile.FileName, relativePath);
            return false;
        }
    }

    public async Task<Stream?> ReadFileAsStreamAsync(string relativePath)
    {
        try
        {
            var containerClient = await GetContainerClientAsync(relativePath);
            if (containerClient == null) return null;
            var blobPath = GetBlobPath(relativePath);
             if (string.IsNullOrEmpty(blobPath)) return null; // Cannot read container root

            BlobClient blobClient = containerClient.GetBlobClient(blobPath);

            if (!await blobClient.ExistsAsync())
            {
                 _logger.LogWarning("Attempted to read non-existent blob as stream: {Container}/{BlobPath}", containerClient.Name, blobPath);
                return null;
            }

            BlobDownloadInfo download = await blobClient.DownloadAsync();
            // Return the content stream directly. Caller is responsible for disposal.
            // For large files, consider DownloadStreamingAsync
            return download.Content;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error reading blob as stream: {RelativePath}", relativePath);
            return null;
        }
    }

     public async Task<string?> ReadFileAsTextAsync(string relativePath)
    {
        var stream = await ReadFileAsStreamAsync(relativePath);
        if (stream == null) return null;

        try
        {
            using var reader = new StreamReader(stream);
            return await reader.ReadToEndAsync();
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error reading blob stream as text: {RelativePath}", relativePath);
             return null;
        }
        finally
        {
            await stream.DisposeAsync(); // Dispose the stream obtained from ReadFileAsStreamAsync
        }
    }

    public async Task<bool> DeleteFileAsync(string relativePath)
    {
        try
        {
            var containerClient = await GetContainerClientAsync(relativePath);
             if (containerClient == null) return false; // Invalid path
            var blobPath = GetBlobPath(relativePath);
             if (string.IsNullOrEmpty(blobPath)) return false; // Cannot delete container root

            BlobClient blobClient = containerClient.GetBlobClient(blobPath);
            var result = await blobClient.DeleteIfExistsAsync();
             _logger.LogDebug("Delete blob result for {Container}/{BlobPath}: Existed={Existed}", containerClient.Name, blobPath, result.Value);
            return true; // Success even if it didn't exist
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error deleting blob: {RelativePath}", relativePath);
            return false;
        }
    }

    public async Task<bool> FileExistsAsync(string relativePath)
    {
        try
        {
             var containerClient = await GetContainerClientAsync(relativePath);
             if (containerClient == null) return false;
             var blobPath = GetBlobPath(relativePath);
              if (string.IsNullOrEmpty(blobPath)) return false; // Container root doesn't count as a file

             BlobClient blobClient = containerClient.GetBlobClient(blobPath);
             return await blobClient.ExistsAsync();
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error checking blob existence: {RelativePath}", relativePath);
             return false;
        }
    }

    public async Task<bool> MoveFileAsync(string sourceRelativePath, string destinationRelativePath)
    {
        // Blob storage doesn't have a direct move. It's copy + delete.
        // Ensure source and destination are in the same container for simplicity,
        // otherwise, it gets more complex (cross-container copy).
        try
        {
            var sourceContainerClient = await GetContainerClientAsync(sourceRelativePath);
            var destContainerClient = await GetContainerClientAsync(destinationRelativePath);

            if (sourceContainerClient == null || destContainerClient == null || sourceContainerClient.Name != destContainerClient.Name)
            {
                _logger.LogError("MoveFile failed: Source and destination must be in the same container (Data or Data2). Source: {Source}, Dest: {Dest}", sourceRelativePath, destinationRelativePath);
                return false;
            }

            var sourceBlobPath = GetBlobPath(sourceRelativePath);
            var destBlobPath = GetBlobPath(destinationRelativePath);

             if (string.IsNullOrEmpty(sourceBlobPath) || string.IsNullOrEmpty(destBlobPath))
             {
                  _logger.LogError("MoveFile failed: Invalid source or destination blob path. Source: {Source}, Dest: {Dest}", sourceBlobPath, destBlobPath);
                  return false;
             }

            BlobClient sourceBlob = sourceContainerClient.GetBlobClient(sourceBlobPath);
            BlobClient destBlob = destContainerClient.GetBlobClient(destBlobPath);

            if (!await sourceBlob.ExistsAsync())
            {
                 _logger.LogWarning("MoveFile failed: Source blob not found: {Container}/{SourceBlob}", sourceContainerClient.Name, sourceBlobPath);
                 return false;
            }
             if (await destBlob.ExistsAsync())
            {
                 _logger.LogWarning("MoveFile failed: Destination blob already exists: {Container}/{DestBlob}", destContainerClient.Name, destBlobPath);
                 return false;
            }

            // Start copy operation
            CopyFromUriOperation copyOperation = await destBlob.StartCopyFromUriAsync(sourceBlob.Uri);
            await copyOperation.WaitForCompletionAsync(); // Wait for copy to finish

            // Check copy status (optional but recommended)
            Response<BlobProperties> destProperties = await destBlob.GetPropertiesAsync();
            if (destProperties.Value.CopyStatus != CopyStatus.Success)
            {
                 _logger.LogError("MoveFile failed: Copy operation status was {Status} for {Container}/{DestBlob}", destProperties.Value.CopyStatus, destContainerClient.Name, destBlobPath);
                 // Optionally try to delete the potentially partially copied destination blob
                 await destBlob.DeleteIfExistsAsync();
                 return false;
            }

            // Delete the source blob
            await sourceBlob.DeleteAsync();

             _logger.LogDebug("Moved blob from {Source} to {Destination} in container {Container}", sourceBlobPath, destBlobPath, sourceContainerClient.Name);
            return true;
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error moving blob from {SourceRelativePath} to {DestinationRelativePath}", sourceRelativePath, destinationRelativePath);
            return false;
        }
    }

    public async Task<List<string>> ListFilesAsync(string relativeDirectoryPath)
    {
        var files = new List<string>();
        try
        {
            var containerClient = await GetContainerClientAsync(relativeDirectoryPath);
             if (containerClient == null) return files; // Invalid path prefix

            var directoryBlobPrefix = GetBlobPath(relativeDirectoryPath);
            // Ensure prefix ends with '/' if it's not empty, to list items *within* the virtual directory
            if (!string.IsNullOrEmpty(directoryBlobPrefix) && !directoryBlobPrefix.EndsWith('/'))
            {
                directoryBlobPrefix += "/";
            }

            await foreach (BlobItem blobItem in containerClient.GetBlobsAsync(prefix: directoryBlobPrefix))
            {
                // Remove the prefix to get the relative filename within the directory
                var fileName = blobItem.Name.Substring(directoryBlobPrefix.Length);
                // Only add if it's directly within the directory (no further slashes)
                if (!string.IsNullOrEmpty(fileName) && !fileName.Contains('/'))
                {
                    files.Add(fileName);
                }
            }
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error listing blobs in directory: {RelativeDirectoryPath}", relativeDirectoryPath);
        }
        return files;
    }

     public async Task<List<string>> ListSubdirectoriesAsync(string relativeDirectoryPath)
    {
        var dirs = new HashSet<string>(); // Use HashSet to avoid duplicates
         try
        {
            var containerClient = await GetContainerClientAsync(relativeDirectoryPath);
             if (containerClient == null) return dirs.ToList();

            var directoryBlobPrefix = GetBlobPath(relativeDirectoryPath);
            if (!string.IsNullOrEmpty(directoryBlobPrefix) && !directoryBlobPrefix.EndsWith('/'))
            {
                directoryBlobPrefix += "/";
            }

            // List blobs hierarchically to find virtual directories
            await foreach (BlobHierarchyItem item in containerClient.GetBlobsByHierarchyAsync(prefix: directoryBlobPrefix, delimiter: "/"))
            {
                if (item.IsPrefix) // IsPrefix indicates a virtual directory
                {
                    // Extract the directory name (remove prefix and trailing '/')
                    var dirName = item.Prefix.Substring(directoryBlobPrefix.Length).TrimEnd('/');
                     if (!string.IsNullOrEmpty(dirName))
                     {
                        dirs.Add(dirName);
                     }
                }
            }
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error listing blob subdirectories in: {RelativeDirectoryPath}", relativeDirectoryPath);
        }
        return dirs.ToList();
    }

    public async Task<bool> DeleteDirectoryAsync(string relativeDirectoryPath)
    {
        // Deleting a "directory" in blob storage means deleting all blobs with that prefix.
         try
        {
            var containerClient = await GetContainerClientAsync(relativeDirectoryPath);
             if (containerClient == null) return false; // Invalid path

            var directoryBlobPrefix = GetBlobPath(relativeDirectoryPath);
            if (string.IsNullOrEmpty(directoryBlobPrefix)) {
                 _logger.LogError("Cannot delete container root via DeleteDirectoryAsync: {RelativePath}", relativeDirectoryPath);
                 return false; // Safety: Don't allow deleting everything via empty prefix
            }
            // Ensure prefix ends with '/' to avoid deleting blobs that merely start with the name
            if (!directoryBlobPrefix.EndsWith('/'))
            {
                directoryBlobPrefix += "/";
            }

            int deleteCount = 0;
            await foreach (BlobItem blobItem in containerClient.GetBlobsAsync(prefix: directoryBlobPrefix))
            {
                await containerClient.DeleteBlobIfExistsAsync(blobItem.Name);
                deleteCount++;
            }
             _logger.LogDebug("Deleted {Count} blobs with prefix {Prefix} in container {Container}", deleteCount, directoryBlobPrefix, containerClient.Name);
            return true;
        }
        catch (Exception ex)
        {
             _logger.LogError(ex, "Error deleting blobs in directory: {RelativeDirectoryPath}", relativeDirectoryPath);
            return false;
        }
    }

    public string? GetFileUrl(string relativePath)
    {
        // For private containers, we'd need to generate a SAS token URL.
        // For simplicity, returning null as direct public URLs aren't assumed.
        // If containers were public, we could construct the URL.
        try
        {
             var containerClient = GetContainerClientAsync(relativePath).GetAwaiter().GetResult(); // Blocking for simplicity in non-async method
             if (containerClient == null) return null;
             var blobPath = GetBlobPath(relativePath);
              if (string.IsNullOrEmpty(blobPath)) return null;

             BlobClient blobClient = containerClient.GetBlobClient(blobPath);
             // This is the direct URL, but access depends on container permissions
             // return blobClient.Uri.ToString();
             return null; // Return null as we assume private containers
        }
         catch (Exception ex)
        {
             _logger.LogError(ex, "Error getting blob URL for: {RelativePath}", relativePath);
             return null;
        }
    }

     public async Task<bool> CopyLocalFileToStorageAsync(string localSourcePath, string destinationRelativePath, bool overwrite = false)
     {
          try
         {
             if (!File.Exists(localSourcePath))
             {
                 _logger.LogError("CopyLocalFile failed: Source file not found: {SourcePath}", localSourcePath);
                 return false;
             }

             var containerClient = await GetContainerClientAsync(destinationRelativePath);
             if (containerClient == null) return false;
             var blobPath = GetBlobPath(destinationRelativePath);
              if (string.IsNullOrEmpty(blobPath)) {
                 _logger.LogError("Cannot copy file to container root: {RelativePath}", destinationRelativePath);
                 return false;
             }

             BlobClient blobClient = containerClient.GetBlobClient(blobPath);

             if (!overwrite && await blobClient.ExistsAsync())
             {
                  _logger.LogWarning("CopyLocalFile skipped: Destination blob already exists and overwrite is false: {Container}/{BlobPath}", containerClient.Name, blobPath);
                  return true; // Considered success
             }

             await using var stream = File.OpenRead(localSourcePath);
             await blobClient.UploadAsync(stream, overwrite: overwrite);

             _logger.LogDebug("Copied local file from {SourcePath} to blob {Container}/{BlobPath}", localSourcePath, containerClient.Name, blobPath);
             return true;
         }
         catch (Exception ex)
         {
              _logger.LogError(ex, "Error copying local file from {SourcePath} to {DestinationRelativePath}", localSourcePath, destinationRelativePath);
             return false;
         }
     }
}

<?php

/**
 * The Kinsmen File Manager v3.0
 *
 * A comprehensive, modern file manager with CWP dark theme styling:
 * - File Tree Navigation
 * - Search functionality
 * - Create/Edit/Delete files and folders
 * - Upload/Download files
 * - Copy/Move operations
 * - Compression (zip, tar, gzip)
 * - Extraction (zip, tar, gzip)
 * - Deletion confirmations
 * - Rename operations
 * - Permission management
 * - Drag and drop support
 * - Multi-select operations
 * - Sorting and filtering
 */




// CWP Usage
$cwp = true;


if ($cwp) {
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $segments = explode("/", trim($path, "/"));

    $url_token = null;
    $url_username = null;

    foreach ($segments as $index => $segment) {
        if ($segment === "filemanager.php" && $index > 0) {
            $url_username = $segments[$index - 1];
            if ($index > 1) {
                $url_token = $segments[$index - 2];
            }
            break;
        }
    }

    if (!$url_username || !$url_token) {
        header("HTTP/1.1 403 Forbidden");
        echo "Access denied. Invalid URL format.";
        exit();
    }

    $token_dir = "/home/$url_username/.tokens/";
    $token_file = $token_dir . $url_token;

    if (!file_exists($token_file)) {
        header("HTTP/1.1 403 Forbidden");
        echo "Access denied. Invalid token.";
        exit();
    }

    $token_data = json_decode(file_get_contents($token_file), true);

    if (time() > $token_data["expiry"]) {
        unlink($token_file);
        header("HTTP/1.1 403 Forbidden");
        echo "Access denied. Token expired.";
        exit();
    }

    if ($token_data["username"] !== $url_username) {
        header("HTTP/1.1 403 Forbidden");
        echo "Access denied. Username mismatch.";
        exit();
    }

    $username = $token_data["username"];
    $root_path = "/home/$username";
} else {
    $username = "joe";
    $root_path = $_SERVER['DOCUMENT_ROOT'] . "/001_public";
}


// Configuration
$config = [
    "root_path" => $root_path,
    "allowed_extensions" => ["*"],
    "timezone" => "Africa/Lagos",
    "date_format" => "M j Y, g:i A",
    "font_size" => "16px",
];

if (file_exists("$root_path/.fm-config")) {
    $userConfig = "$root_path/.fm-config";
    $settings = json_decode(file_get_contents($userConfig), true);

    $config['timezone'] = $settings['timezone'] ?? $config['timezone'];
    $config['date_format'] = $settings['date_format'] ?? $config['date_format'];
    $config['font_size'] = $settings['font_size'] ?? $config['font_size'];
}

// Set timezone
date_default_timezone_set($config["timezone"]);

// Security check function
function securityCheck($path)
{
    global $config;
    $realPath = realpath($path);
    if (!$realPath) {
        return $config["root_path"];
    }
    return strpos($realPath, $config["root_path"]) === 0;
}

// Helper function to format file size
function formatSize($bytes)
{
    $units = ["bytes", "KB", "MB", "GB", "TB"];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . " " . $units[$i];
}

// Helper function to get file icon based on extension
function getFileIcon($file)
{
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    $iconMap = [
        "pdf"   => "<i class='fas fa-file-pdf' style='color:#ef4444'></i>",
        "doc"   => "<i class='fas fa-file-word' style='color:#3b82f6'></i>",
        "docx"  => "<i class='fas fa-file-word' style='color:#3b82f6'></i>",
        "xls"   => "<i class='fas fa-file-excel' style='color:#22c55e'></i>",
        "xlsx"  => "<i class='fas fa-file-excel' style='color:#22c55e'></i>",
        "ppt"   => "<i class='fas fa-file-powerpoint' style='color:#f97316'></i>",
        "pptx"  => "<i class='fas fa-file-powerpoint' style='color:#f97316'></i>",
        "jpg"   => "<i class='fas fa-file-image' style='color:#a78bfa'></i>",
        "jpeg"  => "<i class='fas fa-file-image' style='color:#a78bfa'></i>",
        "png"   => "<i class='fas fa-file-image' style='color:#a78bfa'></i>",
        "gif"   => "<i class='fas fa-file-image' style='color:#a78bfa'></i>",
        "txt"   => "<i class='fas fa-file-alt' style='color:#94a3b8'></i>",
        "zip"   => "<i class='fas fa-file-archive' style='color:#fbbf24'></i>",
        "tar"   => "<i class='fas fa-file-archive' style='color:#fbbf24'></i>",
        "gz"    => "<i class='fas fa-file-archive' style='color:#fbbf24'></i>",
        "html"  => "<i class='fas fa-file-code' style='color:#f97316'></i>",
        "htm"   => "<i class='fas fa-file-code' style='color:#f97316'></i>",
        "css"   => "<i class='fas fa-file-code' style='color:#38bdf8'></i>",
        "js"    => "<i class='fas fa-file-code' style='color:#facc15'></i>",
        "php"   => "<i class='fas fa-file-code' style='color:#a78bfa'></i>",
        "py"    => "<i class='fas fa-file-code' style='color:#60a5fa'></i>",
        "java"  => "<i class='fas fa-file-code' style='color:#fb923c'></i>",
        "c"     => "<i class='fas fa-file-code' style='color:#94a3b8'></i>",
        "cpp"   => "<i class='fas fa-file-code' style='color:#38bdf8'></i>",
        "mp3"   => "<i class='fas fa-file-audio' style='color:#c084fc'></i>",
        "mp4"   => "<i class='fas fa-file-video' style='color:#34d399'></i>",
        "mov"   => "<i class='fas fa-file-video' style='color:#34d399'></i>",
        "avi"   => "<i class='fas fa-file-video' style='color:#34d399'></i>",
    ];


    if (is_dir($file)) {
        return "<i class='fas fa-folder folder-icon'></i>";
    } elseif (isset($iconMap[$extension])) {
        return $iconMap[$extension];
    } else {
        return "<i class='fas fa-file' style='color:#64748b'></i>";
    }
}

// Function to get file permissions as string
function getPermissions($file)
{
    $perms = fileperms($file);

    if (($perms & 0xc000) == 0xc000) {
        $info = "s";
    } elseif (($perms & 0xa000) == 0xa000) {
        $info = "l";
    } elseif (($perms & 0x8000) == 0x8000) {
        $info = "-";
    } elseif (($perms & 0x6000) == 0x6000) {
        $info = "b";
    } elseif (($perms & 0x4000) == 0x4000) {
        $info = "d";
    } elseif (($perms & 0x2000) == 0x2000) {
        $info = "c";
    } elseif (($perms & 0x1000) == 0x1000) {
        $info = "p";
    } else {
        $info = "u";
    }

    $info .= $perms & 0x0100 ? "r" : "-";
    $info .= $perms & 0x0080 ? "w" : "-";
    $info .= $perms & 0x0040 ? ($perms & 0x0800 ? "s" : "x") : ($perms & 0x0800 ? "S" : "-");
    $info .= $perms & 0x0020 ? "r" : "-";
    $info .= $perms & 0x0010 ? "w" : "-";
    $info .= $perms & 0x0008 ? ($perms & 0x0400 ? "s" : "x") : ($perms & 0x0400 ? "S" : "-");
    $info .= $perms & 0x0004 ? "r" : "-";
    $info .= $perms & 0x0002 ? "w" : "-";
    $info .= $perms & 0x0001 ? ($perms & 0x0200 ? "t" : "x") : ($perms & 0x0200 ? "T" : "-");

    return $info;
}

// Function to build directory tree
function buildDirectoryTree($dir, $relativePath = "")
{
    global $config;

    $result = [];
    $cdir = scandir($dir);

    foreach ($cdir as $key => $value) {
        if (!in_array($value, [".", ".."])) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $value;
            $relPathPrefix = $relativePath ? ltrim($relativePath, "/") . "/" : "";
            $relPath = $relPathPrefix . $value;

            if (is_dir($fullPath)) {
                if (securityCheck($fullPath)) {
                    $result[] = [
                        "name" => $value,
                        "type" => "dir",
                        "path" => "/" . ltrim($relPath, "/"),
                        "children" => buildDirectoryTree($fullPath, $relPath),
                    ];
                }
            }
        }
    }

    return $result;
}

// Function to get directory contents
function getDirectoryContents($dir, $sort = "name", $order = "asc")
{
    $result = [];
    $cdir = scandir($dir);

    foreach ($cdir as $key => $value) {
        if (!in_array($value, [".", ".."])) {
            $fullPath = $dir . DIRECTORY_SEPARATOR . $value;

            $fileInfo = [
                "name" => $value,
                "type" => is_dir($fullPath) ? "dir" : "file",
                "size" => is_dir($fullPath) ? "" : formatSize(filesize($fullPath)),
                "size_raw" => is_dir($fullPath) ? 0 : filesize($fullPath),
                "extension" => is_dir($fullPath) ? "" : pathinfo($value, PATHINFO_EXTENSION),
                "date_added" => date($GLOBALS["config"]["date_format"], filectime($fullPath)),
                "last_modified" => date($GLOBALS["config"]["date_format"], filemtime($fullPath)),
                "permissions" => getPermissions($fullPath),
                "icon" => getFileIcon($fullPath),
            ];

            $result[] = $fileInfo;
        }
    }

    usort($result, function ($a, $b) use ($sort, $order) {
        if ($a["type"] != $b["type"]) {
            return $a["type"] == "dir" ? -1 : 1;
        }
        $valA = $a[$sort];
        $valB = $b[$sort];
        if ($sort == "size") {
            $valA = $a["size_raw"];
            $valB = $b["size_raw"];
        }
        if ($order == "asc") {
            return $valA <=> $valB;
        } else {
            return $valB <=> $valA;
        }
    });

    return $result;
}

function createDirectory($path, $name)
{
    $dirPath = $path . DIRECTORY_SEPARATOR . $name;
    if (!file_exists($dirPath)) {
        if (mkdir($dirPath, 0755)) {
            return ["status" => "success", "message" => "Directory created successfully"];
        } else {
            return ["status" => "error", "message" => "Failed to create directory"];
        }
    } else {
        return ["status" => "error", "message" => "Directory already exists"];
    }
}

function createFile($path, $name, $content = "")
{
    $filePath = $path . DIRECTORY_SEPARATOR . $name;
    if (!file_exists($filePath)) {
        if (file_put_contents($filePath, $content) !== false) {
            return ["status" => "success", "message" => "File created successfully"];
        } else {
            return ["status" => "error", "message" => "Failed to create file"];
        }
    } else {
        return ["status" => "error", "message" => "File already exists"];
    }
}

function updateSettings($timezone, $dateFormat, $fontSize)
{
    global $config;
    if (empty($timezone) && empty($dateFormat) && empty($fontSize)) {
        return ["status" => "error", "message" => "No changes made"];
    }
    $data = [
        'timezone' => empty($timezone) ? $config["timezone"] : $timezone,
        'date_format' => empty($dateFormat) ? $config["date_format"] : $dateFormat,
        'font_size' => empty($fontSize) ? $config["font_size"] : $fontSize,
    ];
    $file = $config["root_path"] . "/.fm-config";
    if (file_put_contents($file, json_encode($data))) {
        return ["status" => "success", "message" => "Settings updated successfully", "type" => "confirm"];
    } else {
        return ["status" => "error", "message" => "Failed to update settings"];
    }
}

function renameItem($path, $oldName, $newName)
{
    $oldPath = $path . DIRECTORY_SEPARATOR . $oldName;
    $newPath = $path . DIRECTORY_SEPARATOR . $newName;
    if (file_exists($oldPath) && !file_exists($newPath)) {
        if (rename($oldPath, $newPath)) {
            return ["status" => "success", "message" => "Item renamed successfully"];
        } else {
            return ["status" => "error", "message" => "Failed to rename item"];
        }
    } else {
        return ["status" => "error", "message" => "Source does not exist or destination already exists"];
    }
}

function deleteItem($path)
{
    if (is_dir($path)) {
        $files = array_diff(scandir($path), [".", ".."]);
        foreach ($files as $file) {
            deleteItem($path . DIRECTORY_SEPARATOR . $file);
        }
        if (rmdir($path)) {
            return true;
        } else {
            return false;
        }
    } else {
        if (unlink($path)) {
            return true;
        } else {
            return false;
        }
    }
}

function copyItem($source, $destination)
{
    if (is_dir($source)) {
        if (!file_exists($destination)) {
            mkdir($destination, 0755);
        }
        $files = array_diff(scandir($source), [".", ".."]);
        foreach ($files as $file) {
            copyItem($source . DIRECTORY_SEPARATOR . $file, $destination . DIRECTORY_SEPARATOR . $file);
        }
        return ["status" => "success", "message" => "Directory copied successfully"];
    } else {
        if (copy($source, $destination)) {
            return ["status" => "success", "message" => "File copied successfully"];
        } else {
            return ["status" => "error", "message" => "Failed to copy file"];
        }
    }
}

function changePermissions($path, $mode)
{
    if (chmod($path, octdec($mode))) {
        return ["status" => "success", "message" => "Permissions changed successfully"];
    } else {
        return ["status" => "error", "message" => "Failed to change permissions"];
    }
}

function compressItems($items, $destination, $type = "zip")
{
    global $config;

    if (count($items) < 1) {
        return ["status" => "error", "message" => "No selected items to compress"];
    }

    switch ($type) {
        case "empty_trash":
            $trashDir = $config["root_path"] . "/.trash";
            if (!is_dir($trashDir)) {
                $response = ["status" => "error", "message" => "Trash directory not found"];
                break;
            }
            $items = scandir($trashDir);
            $deleted = 0;
            foreach ($items as $item) {
                if ($item !== "." && $item !== "..") {
                    $path = $trashDir . "/" . $item;
                    if (is_file($path) || is_link($path)) {
                        if (@unlink($path)) $deleted++;
                    } elseif (is_dir($path)) {
                        $cmd = "rm -rf " . escapeshellarg($path);
                        @exec($cmd, $out, $code);
                        if ($code === 0) $deleted++;
                    }
                }
            }
            $response = ["status" => "success", "message" => "Trash emptied ($deleted item(s) deleted)"];
            break;
        case "zip":
            $zip = new ZipArchive();
            if ($zip->open($destination, ZipArchive::CREATE) === true) {
                foreach ($items as $item) {
                    if (is_dir($item)) {
                        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($item), RecursiveIteratorIterator::LEAVES_ONLY);
                        foreach ($files as $file) {
                            if (!$file->isDir()) {
                                $filePath = $file->getRealPath();
                                $relativePath = substr($filePath, strlen(dirname($item)) + 1);
                                $zip->addFile($filePath, $relativePath);
                            }
                        }
                    } else {
                        $zip->addFile($item, basename($item));
                    }
                }
                $zip->close();
                return ["status" => "success", "message" => "Files compressed successfully (ZIP)"];
            } else {
                return ["status" => "error", "message" => "Failed to create ZIP archive"];
            }
            break;
        case "tar":
            $phar = new PharData($destination);
            foreach ($items as $item) {
                $phar->addFile($item, basename($item));
            }
            return ["status" => "success", "message" => "Files compressed successfully (TAR)"];
            break;
        case "gzip":
            if (count($items) > 1) {
                return ["status" => "error", "message" => "GZip can only compress one file at a time"];
            }
            $content = file_get_contents($items[0]);
            $gzContent = gzencode($content, 9);
            file_put_contents($destination, $gzContent);
            return ["status" => "success", "message" => "File compressed successfully (GZip)"];
            break;
        default:
            return ["status" => "error", "message" => "Unknown compression type"];
    }
}

function moveToTrash($source, $currentPath, $config)
{
    $trashDir = $config["root_path"] . "/.trash";
    if (!file_exists($trashDir)) {
        mkdir($trashDir, 0755, true);
    }
    $itemName = basename($source);
    $destinationPath = $trashDir . "/" . $itemName;
    $counter = 1;
    $originalName = pathinfo($itemName, PATHINFO_FILENAME);
    $extension = pathinfo($itemName, PATHINFO_EXTENSION);
    while (file_exists($destinationPath)) {
        if ($extension) {
            $destinationPath = $trashDir . "/" . $originalName . " (" . $counter . ")." . $extension;
        } else {
            $destinationPath = $trashDir . "/" . $originalName . " (" . $counter . ")";
        }
        $counter++;
    }
    if (rename($source, $destinationPath)) {
        $metaFilename = $destinationPath . ".trashinfo";
        $metadata = [
            "original_path" => str_replace($config["root_path"], "", $currentPath) . "/" . $itemName,
            "deletion_date" => date("Y-m-d H:i:s"),
            "original_name" => $itemName,
        ];
        file_put_contents($metaFilename, json_encode($metadata, JSON_PRETTY_PRINT));
        return true;
    }
    return false;
}

function restoreFromTrash($source, $config)
{
    $metaFilename = $source . ".trashinfo";
    if (!file_exists($metaFilename)) {
        return ["status" => "error", "message" => "Trash metadata not found"];
    }
    $metadata = json_decode(file_get_contents($metaFilename), true);
    if (!$metadata || !isset($metadata["original_path"])) {
        return ["status" => "error", "message" => "Invalid trash metadata"];
    }
    $destPath = $config["root_path"] . $metadata["original_path"];
    $destDir = dirname($destPath);
    if (!file_exists($destDir)) {
        if (!mkdir($destDir, 0755, true)) {
            return ["status" => "error", "message" => "Failed to create destination directory"];
        }
    }
    if (file_exists($destPath)) {
        $originalName = pathinfo($destPath, PATHINFO_FILENAME);
        $extension = pathinfo($destPath, PATHINFO_EXTENSION);
        $counter = 1;
        $newDestPath = $destPath;
        while (file_exists($newDestPath)) {
            if ($extension) {
                $newDestPath = $destDir . "/" . $originalName . " (restored " . $counter . ")." . $extension;
            } else {
                $newDestPath = $destDir . "/" . $originalName . " (restored " . $counter . ")";
            }
            $counter++;
        }
        $destPath = $newDestPath;
    }
    if (rename($source, $destPath)) {
        if (file_exists($metaFilename)) {
            unlink($metaFilename);
        }
        return ["status" => "success", "message" => "Item restored successfully"];
    }
    return ["status" => "error", "message" => "Failed to restore item"];
}

function extractArchive($source, $destination, $config)
{
    if (!file_exists($destination)) {
        if (!mkdir($destination, 0755, true)) {
            return ["status" => "error", "message" => "Failed to create destination directory"];
        }
    }
    $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
    switch ($extension) {
        case "zip":
            return extractZip($source, $destination);
        case "tar":
            return extractTar($source, $destination);
        case "gz":
        case "gzip":
            return extractGzip($source, $destination);
        case "bz2":
        case "bzip2":
            return extractBzip2($source, $destination);
        case "rar":
            return extractRar($source, $destination);
        case "7z":
            return extract7Zip($source, $destination);
        default:
            return ["status" => "error", "message" => "Unsupported archive type: " . $extension];
    }
}

function extractZip($source, $destination)
{
    $zip = new ZipArchive();
    $res = $zip->open($source);
    if ($res === true) {
        $zip->extractTo($destination);
        $zip->close();
        return ["status" => "success", "message" => "ZIP archive extracted successfully"];
    } else {
        return ["status" => "error", "message" => "Failed to open ZIP archive (Error code: " . $res . ")"];
    }
}

function extractTar($source, $destination)
{
    try {
        $phar = new PharData($source);
        $phar->extractTo($destination, null, true);
        return ["status" => "success", "message" => "TAR archive extracted successfully"];
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Failed to extract TAR archive: " . $e->getMessage()];
    }
}

function extractGzip($source, $destination)
{
    $basename = basename($source, ".gz");
    if (substr($basename, -4) === ".tar") {
        try {
            $phar = new PharData($source);
            $phar->extractTo($destination, null, true);
            return ["status" => "success", "message" => "TAR.GZ archive extracted successfully"];
        } catch (Exception $e) {
            return ["status" => "error", "message" => "Failed to extract TAR.GZ archive: " . $e->getMessage()];
        }
    } else {
        $destFile = $destination . DIRECTORY_SEPARATOR . $basename;
        $sfp = gzopen($source, "rb");
        $fp = fopen($destFile, "wb");
        if (!$sfp || !$fp) {
            return ["status" => "error", "message" => "Failed to open files for extraction"];
        }
        while (!gzeof($sfp)) {
            fwrite($fp, gzread($sfp, 4096));
        }
        gzclose($sfp);
        fclose($fp);
        return ["status" => "success", "message" => "GZIP file extracted successfully"];
    }
}

function extractBzip2($source, $destination)
{
    $basename = basename($source, ".bz2");
    if (substr($basename, -4) === ".tar") {
        try {
            $phar = new PharData($source);
            $phar->extractTo($destination, null, true);
            return ["status" => "success", "message" => "TAR.BZ2 archive extracted successfully"];
        } catch (Exception $e) {
            return ["status" => "error", "message" => "Failed to extract TAR.BZ2 archive: " . $e->getMessage()];
        }
    } else {
        $destFile = $destination . DIRECTORY_SEPARATOR . $basename;
        $sfp = bzopen($source, "r");
        $fp = fopen($destFile, "w");
        if (!$sfp || !$fp) {
            return ["status" => "error", "message" => "Failed to open files for extraction"];
        }
        while (!feof($sfp)) {
            fwrite($fp, bzread($sfp, 4096));
        }
        bzclose($sfp);
        fclose($fp);
        return ["status" => "success", "message" => "BZIP2 file extracted successfully"];
    }
}

function extractRar($source, $destination)
{
    if (class_exists("RarArchive")) {
        $rar = RarArchive::open($source);
        if ($rar === false) {
            return ["status" => "error", "message" => "Failed to open RAR archive"];
        }
        $entries = $rar->getEntries();
        foreach ($entries as $entry) {
            $entry->extract($destination);
        }
        $rar->close();
        return ["status" => "success", "message" => "RAR archive extracted successfully"];
    } elseif (function_exists("exec")) {
        $command = "unrar x -o+ " . escapeshellarg($source) . " " . escapeshellarg($destination);
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            return ["status" => "success", "message" => "RAR archive extracted successfully using unrar command"];
        } else {
            return ["status" => "error", "message" => "Failed to extract RAR archive using unrar command"];
        }
    } else {
        return ["status" => "error", "message" => "RAR extraction not supported - PHP RAR extension or exec() function required"];
    }
}

function extract7Zip($source, $destination)
{
    if (function_exists("exec")) {
        $command = "7z x " . escapeshellarg($source) . " -o" . escapeshellarg($destination) . " -y";
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            return ["status" => "success", "message" => "7Zip archive extracted successfully"];
        } else {
            return ["status" => "error", "message" => "Failed to extract 7Zip archive: " . implode("\n", $output)];
        }
    } else {
        return ["status" => "error", "message" => "7Zip extraction not supported - exec() function required"];
    }
}

// Handle File Manager Operations
if (isset($_POST["action"]) || isset($_GET["action"])) {
    $action = isset($_POST["action"]) ? $_POST["action"] : $_GET["action"];
    $response = ["status" => "error", "message" => "Unknown action"];

    $currentPath = isset($_POST["path"]) ? $config["root_path"] . $_POST["path"] : $config["root_path"];
    if (isset($_GET["path"])) {
        $currentPath = $config["root_path"] . $_GET["path"];
    }

    if (!securityCheck($currentPath) && $action != "search") {
        $response = ["status" => "error", "message" => "Security violation"];
    } else {
        switch ($action) {
            case "settings":
                $tmz = isset($_POST["timezone"]) ? $_POST["timezone"] : "";
                $dt = isset($_POST["dateformat"]) ? $_POST["dateformat"] : "";
                $font = isset($_POST["fontSize"]) ? $_POST["fontSize"] : "";
                $response = updateSettings($tmz, $dt, $font);
                break;
            case "list":
                $sort = isset($_POST["sort"]) ? $_POST["sort"] : (isset($_GET["sort"]) ? $_GET["sort"] : "name");
                $order = isset($_POST["order"]) ? $_POST["order"] : (isset($_GET["order"]) ? $_GET["order"] : "asc");
                $response = [
                    "status" => "success",
                    "data" => getDirectoryContents($currentPath, $sort, $order),
                    "current_path" => str_replace($config["root_path"], "", $currentPath),
                ];
                break;
            case "tree":
                $response = ["status" => "success", "data" => buildDirectoryTree($config["root_path"])];
                break;
            case "create_dir":
                $name = isset($_POST["name"]) ? $_POST["name"] : "";
                $response = createDirectory($currentPath, $name);
                break;
            case "create_file":
                $name = isset($_POST["name"]) ? $_POST["name"] : "";
                $content = isset($_POST["content"]) ? $_POST["content"] : "";
                $response = createFile($currentPath, $name, $content);
                break;
            case "rename":
                $oldName = isset($_POST["old_name"]) ? $_POST["old_name"] : "";
                $newName = isset($_POST["new_name"]) ? $_POST["new_name"] : "";
                $response = renameItem($currentPath, $oldName, $newName);
                break;
            case "trash":
                $itemsArray = isset($_POST["items"]) ? $_POST["items"] : "";
                $items = explode(",", $itemsArray);
                $status = true;
                $movedCount = 0;
                $failedCount = 0;
                if ($itemsArray == "") {
                    $response = ["status" => "error", "message" => "No items selected"];
                    break;
                }
                foreach ($items as $item) {
                    $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;
                    if (!file_exists($itemPath)) {
                        continue;
                    }
                    if (moveToTrash($itemPath, $currentPath, $config)) {
                        $movedCount++;
                    } else {
                        $failedCount++;
                        $status = false;
                    }
                }
                $response = ["status" => $status ? "success" : "error", "message" => "Moved $movedCount item(s) to trash" . ($failedCount > 0 ? ", Failed $failedCount item(s)" : "")];
                break;
            case "delete":
                $itemsArray = isset($_POST["items"]) ? $_POST["items"] : "";
                $status = true;
                $movedCount = 0;
                $failedCount = 0;
                $items = explode(",", $itemsArray);
                if ($itemsArray == "") {
                    $response = ["status" => "error", "message" => "No items selected"];
                    break;
                }
                foreach ($items as $item) {
                    $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;
                    if (!file_exists($itemPath)) {
                        continue;
                    }
                    if (deleteItem($itemPath)) {
                        $movedCount++;
                    } else {
                        $failedCount++;
                        $status = false;
                    }
                }
                $response = ["status" => $status ? "success" : "error", "message" => "Deleted $movedCount item(s) successfully" . ($failedCount > 0 ? ", $failedCount item(s) Failed to delete" : "")];
                break;
            case "restore":
                $itemsArray = isset($_POST["items"]) ? $_POST["items"] : "";
                $items = explode(",", $itemsArray);
                if ($itemsArray == "") {
                    $response = ["status" => "error", "message" => "No items selected"];
                    break;
                }
                $status = true;
                $restoredCount = 0;
                $failedCount = 0;
                $messages = [];
                foreach ($items as $item) {
                    $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;
                    $result = restoreFromTrash($itemPath, $config);
                    if ($result["status"] === "success") {
                        $restoredCount++;
                        $messages[] = "$item restored successfully";
                    } else {
                        $failedCount++;
                        $status = false;
                        $messages[] = "Failed to restore $item: " . $result["message"];
                    }
                }
                $response = ["status" => $status ? "success" : "error", "message" => "Restored $restoredCount item(s)" . ($failedCount > 0 ? ", Failed $failedCount item(s)" : "")];
                break;
            case "copy":
                $itemsArray = isset($_POST["items"]) ? $_POST["items"] : "";
                $items = explode(",", $itemsArray);
                if ($itemsArray == "") {
                    $response = ["status" => "error", "message" => "No items selected"];
                    break;
                }
                $destination = isset($_POST["destination"]) ? $config["root_path"] . $_POST["destination"] : "";
                if (!securityCheck($destination)) {
                    $response = ["status" => "error", "message" => "Security violation on destination"];
                    break;
                }
                $status = true;
                $messages = [];
                foreach ($items as $item) {
                    $sourcePath = $currentPath . DIRECTORY_SEPARATOR . $item;
                    $destPath = $destination . DIRECTORY_SEPARATOR . $item;
                    $result = copyItem($sourcePath, $destPath);
                    if ($result["status"] != "success") {
                        $status = false;
                    }
                    $messages[] = $result["message"];
                }
                $response = ["status" => $status ? "success" : "error", "message" => implode(", ", $messages)];
                break;
            case "move":
                $itemsArray = isset($_POST["items"]) ? $_POST["items"] : "";
                $items = explode(",", $itemsArray);
                if ($itemsArray == "") {
                    $response = ["status" => "error", "message" => "No items selected"];
                    break;
                }
                $destination = isset($_POST["destination"]) ? $config["root_path"] . $_POST["destination"] : "";
                if (!securityCheck($destination)) {
                    $response = ["status" => "error", "message" => "Security violation on destination"];
                    break;
                }
                $status = true;
                $messages = [];
                foreach ($items as $item) {
                    $sourcePath = $currentPath . DIRECTORY_SEPARATOR . $item;
                    $destPath = $destination . DIRECTORY_SEPARATOR . $item;
                    if (!file_exists($destPath)) {
                        if (rename($sourcePath, $destPath)) {
                            $messages[] = "Moved $item successfully";
                        } else {
                            $status = false;
                            $messages[] = "Failed to move $item";
                        }
                    } else {
                        $status = false;
                        $messages[] = "$item already exists in destination";
                    }
                }
                $response = ["status" => $status ? "success" : "error", "message" => implode(", ", $messages)];
                break;
            case "permissions":
                $item = isset($_POST["item"]) ? $_POST["item"] : "";
                $mode = isset($_POST["mode"]) ? $_POST["mode"] : "0644";
                $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;
                $response = changePermissions($itemPath, $mode);
                break;
            case "compress":
                $itemsArray = isset($_POST["items"]) ? $_POST["items"] : "";
                $type = isset($_POST["type"]) ? $_POST["type"] : "zip";
                $name = isset($_POST["name"]) ? $_POST["name"] : "archive_" . time();
                $items = explode(",", $itemsArray);
                $itemPaths = [];
                foreach ($items as $item) {
                    $itemPaths[] = $currentPath . DIRECTORY_SEPARATOR . $item;
                }
                $extension = "";
                switch ($type) {
                    case "zip":
                        $extension = ".zip";
                        break;
                    case "tar":
                        $extension = ".tar";
                        break;
                    case "gzip":
                        $extension = ".gz";
                        break;
                }
                $destination = $currentPath . DIRECTORY_SEPARATOR . $name . $extension;
                $response = compressItems($itemPaths, $destination, $type);
                break;
            case "read_file":
                $item = isset($_POST["item"]) ? $_POST["item"] : "";
                $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;
                if (file_exists($itemPath) && is_file($itemPath)) {
                    $content = file_get_contents($itemPath);
                    $response = ["status" => "success", "data" => ["content" => $content, "editable" => true]];
                } else {
                    $response = ["status" => "error", "message" => "File not found"];
                }
                break;
            case "save_file":
                $item = isset($_POST["item"]) ? $_POST["item"] : "";
                $content = isset($_POST["content"]) ? $_POST["content"] : "";
                $itemPath = $currentPath . DIRECTORY_SEPARATOR . $item;
                if (file_exists($itemPath) && is_file($itemPath)) {
                    if (file_put_contents($itemPath, $content) !== false) {
                        $response = ["status" => "success", "message" => "File saved successfully"];
                    } else {
                        $response = ["status" => "error", "message" => "Failed to save file"];
                    }
                } else {
                    $response = ["status" => "error", "message" => "File not found"];
                }
                break;
            case "search":
                $query = isset($_POST["query"]) ? $_POST["query"] : "";
                $path = isset($_POST["path"]) ? $config["root_path"] . $_POST["path"] : $config["root_path"];
                if (!securityCheck($path)) {
                    $response = ["status" => "error", "message" => "Security violation"];
                    break;
                }
                $results = [];
                if (!empty($query)) {
                    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
                    foreach ($iterator as $file) {
                        if (stripos($file->getFilename(), $query) !== false) {
                            $relativePath = str_replace($config["root_path"], "", $file->getPathname());
                            $results[] = ["name" => $file->getFilename(), "path" => $relativePath, "type" => $file->isDir() ? "dir" : "file", "size" => $file->isDir() ? "" : formatSize($file->getSize()), "last_modified" => date($config["date_format"], $file->getMTime()), "icon" => getFileIcon($file->getPathname())];
                        }
                    }
                }
                $response = ["status" => "success", "data" => $results];
                break;
            case "extract":
                $file = isset($_POST["file"]) ? $_POST["file"] : "";
                $destination = isset($_POST["destination"]) ? $_POST["destination"] : "";
                if (empty($file)) {
                    $response = ["status" => "error", "message" => "No file specified for extraction"];
                    break;
                }
                $filePath = $currentPath . DIRECTORY_SEPARATOR . $file;
                if (empty($destination)) {
                    $fileInfo = pathinfo($file);
                    $destination = $currentPath . DIRECTORY_SEPARATOR . $fileInfo["filename"];
                } else {
                    $destination = $config["root_path"] . $destination;
                }
                if (!file_exists($destination)) {
                    if (!mkdir($destination, 0755, true)) {
                        $response = ["status" => "error", "message" => "Failed to create destination directory"];
                        break;
                    }
                }
                $result = extractArchive($filePath, $destination, $config);
                $response = $result;
                break;
            case "download":
                $file = isset($_GET["file"]) ? $_GET["file"] : "";
                $filePath = $currentPath . DIRECTORY_SEPARATOR . $file;
                if (file_exists($filePath) && is_file($filePath)) {
                    header("Content-Description: File Transfer");
                    header("Content-Type: application/octet-stream");
                    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
                    header("Expires: 0");
                    header("Cache-Control: must-revalidate");
                    header("Pragma: public");
                    header("Content-Length: " . filesize($filePath));
                    readfile($filePath);
                    exit();
                } else {
                    $response = ["status" => "error", "message" => "File not found"];
                }
                break;
        }
    }

    if ($action != "download") {
        header("Content-Type: application/json");
        echo json_encode($response);
        exit();
    }
}


function parseSize($size)
{
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }
    return round($size);
}


if (isset($_FILES["files"])) {
    header("Content-Type: application/json");
    $currentPath = isset($_POST["path"]) ? $config["root_path"] . $_POST["path"] : $config["root_path"];
    if (!securityCheck($currentPath)) {
        echo json_encode(["status" => "error", "message" => "Security violation"]);
        exit();
    }
    $files = $_FILES["files"];
    $uploaded = 0;
    $failed = 0;
    $failedFiles = [];
    for ($i = 0; $i < count($files["name"]); $i++) {
        $fileName = $files["name"][$i];
        $filePath = $currentPath . DIRECTORY_SEPARATOR . $fileName;
        if ($files["error"][$i] !== UPLOAD_ERR_OK) {
            $failed++;
            $errorMsg = "";
            switch ($files["error"][$i]) {
                case UPLOAD_ERR_INI_SIZE:
                    $errorMsg = "File exceeds upload_max_filesize directive in php.ini";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMsg = "File exceeds MAX_FILE_SIZE directive specified in the HTML form";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMsg = "File was only partially uploaded";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMsg = "No file was uploaded";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMsg = "Missing a temporary folder";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMsg = "Failed to write file to disk";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errorMsg = "File upload stopped by extension";
                    break;
                default:
                    $errorMsg = "Unknown upload error";
            }
            $failedFiles[] = $fileName . " (" . $errorMsg . ")";
            continue;
        }
        $maxFileSize = parseSize(ini_get('upload_max_filesize'));
        if ($files["size"][$i] > $maxFileSize) {
            $failed++;
            $failedFiles[] = $fileName . " (Exceeds maximum file size limit)";
            continue;
        }
        if ($config["allowed_extensions"][0] !== "*") {
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($extension, $config["allowed_extensions"])) {
                $failed++;
                $failedFiles[] = $fileName . " (File type not allowed)";
                continue;
            }
        }
        if (move_uploaded_file($files["tmp_name"][$i], $filePath)) {
            $uploaded++;
        } else {
            $failed++;
            $failedFiles[] = $fileName . " (Server error during move)";
        }
    }
    $response = ["status" => $failed == 0 ? "success" : "partial", "message" => "Uploaded $uploaded file(s)" . ($failed > 0 ? ", Failed $failed file(s)" : "")];
    if (!empty($failedFiles)) {
        $response["failedFiles"] = $failedFiles;
    }
    echo json_encode($response);
    exit();
}

$lines = file(__FILE__);
foreach ($lines as $line) {
    if (strpos($line, "* The Kinsmen") !== false) {
        $versionInfo = trim(str_replace("*", "", $line));
        break;
    }
}

$version = explode(" ", $versionInfo);
$version = end($version);

if ($username == null) {
    $username = "";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager — The Kinsmen</title>
    <link rel="icon" href="icon.png" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/coolalertjs@latest/dist/coolalert.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/htmx.org@2.0.6/dist/htmx.min.js"></script>
    <style>
        /* ── Design Tokens ───────────────────────────────────────── */
        :root {
            --bg-deep: #0f1117;
            --bg-main: #1a1d2e;
            --bg-surface: #1e2235;
            --bg-elevated: #242840;
            --bg-hover: rgba(255, 255, 255, 0.04);
            --bg-selected: rgba(59, 130, 246, 0.12);

            --border-subtle: rgba(255, 255, 255, 0.06);
            --border-default: rgba(255, 255, 255, 0.10);
            --border-accent: #3b82f6;

            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --text-link: #60a5fa;
            --text-link-hover: #93c5fd;

            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --accent-success: #10b981;
            --accent-danger: #ef4444;
            --accent-warning: #f59e0b;

            --folder-color: #fbbf24;

            --radius-sm: 4px;
            --radius-md: 6px;
            --radius-lg: 8px;

            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.4);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.5);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.6);

            --font-size: <?= $config["font_size"] ?>;
            --font-mono: 'Fira Code', 'Cascadia Code', 'Courier New', monospace;
        }

        /* ── Reset & Base ────────────────────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: var(--font-size);
            background-color: var(--bg-deep);
            color: var(--text-primary);
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--bg-elevated);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--accent);
        }

        /* ── Top Header ──────────────────────────────────────────── */
        .top-header {
            background: var(--bg-main);
            border-bottom: 1px solid var(--border-subtle);
            padding: 7px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 46px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .top-header .brand img {
            display: block;
        }

        .search-container {
            max-width: 280px;
        }

        .search-container .input-group .form-control {
            background: var(--bg-elevated);
            border: 1px solid var(--border-default);
            color: var(--text-primary);
            font-size: 0.8rem;
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
        }

        .search-container .input-group .form-control:focus {
            background: var(--bg-elevated);
            border-color: var(--accent);
            color: var(--text-primary);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }

        .search-container .input-group .form-control::placeholder {
            color: var(--text-muted);
        }

        .btn-search {
            background: var(--accent);
            border: none;
            color: #fff;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-search:hover {
            background: var(--accent-hover);
            color: #fff;
        }

        .btn-settings {
            background: transparent;
            border: 1px solid var(--border-default);
            color: var(--text-secondary);
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }

        .btn-settings:hover {
            border-color: var(--accent);
            color: var(--text-link);
            background: rgba(59, 130, 246, 0.08);
        }

        /* ── Main Toolbar ────────────────────────────────────────── */
        .main-toolbar {
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border-subtle);
            padding: 6px 14px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 2px;
            min-height: 38px;
        }

        .header-btns {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            font-size: 0.78rem;
            color: var(--text-secondary);
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            transition: all 0.15s;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .header-btns i {
            font-size: 0.72rem;
        }

        .header-btns:hover {
            color: var(--text-link);
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.2);
        }

        .header-btns.disabled {
            color: var(--text-muted) !important;
            pointer-events: none;
            opacity: 0.45;
        }

        .toolbar-divider {
            width: 1px;
            height: 18px;
            background: var(--border-subtle);
            margin: 0 4px;
        }

        /* ── Progress Bar ────────────────────────────────────────── */
        .progress {
            height: 18px;
            border-radius: var(--radius-sm);
            display: none;
            background: var(--bg-elevated);
            border: 1px solid var(--border-default);
            overflow: hidden;
        }

        .progress-bar {
            transition: width 0.3s ease;
            font-size: 0.7rem;
            font-weight: 600;
            line-height: 18px;
            text-align: center;
            color: #fff;
            white-space: nowrap;
            background: linear-gradient(90deg, var(--accent-success), #059669);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        /* ── Layout Shell ────────────────────────────────────────── */
        .fm-layout {
            display: flex;
            height: calc(100vh - 84px);
            /* header + toolbar */
            overflow: hidden;
        }

        /* ── Sidebar ─────────────────────────────────────────────── */
        .sidebar {
            width: 220px;
            min-width: 220px;
            background: var(--bg-main);
            border-right: 1px solid var(--border-subtle);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-path-bar {
            padding: 8px 10px;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            gap: 4px;
        }

        .sidebar-path-bar .form-control {
            background: var(--bg-elevated);
            border: 1px solid var(--border-default);
            color: var(--text-primary);
            font-size: 0.75rem;
            padding: 3px 7px;
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
            min-width: 0;
        }

        .sidebar-path-bar .form-control:focus {
            background: var(--bg-elevated);
            border-color: var(--accent);
            color: var(--text-primary);
            box-shadow: none;
            outline: none;
        }

        .sidebar-path-bar .form-control::placeholder {
            color: var(--text-muted);
        }

        .sidebar-path-bar .btn-icon {
            background: var(--bg-elevated);
            border: 1px solid var(--border-default);
            color: var(--text-secondary);
            padding: 3px 8px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }

        .sidebar-path-bar .btn-icon:first-child {
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
            border-right: none;
        }

        .sidebar-path-bar .btn-icon:last-child {
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        }

        .sidebar-path-bar .btn-icon:hover {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .collapse-all-btn {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-align: center;
            padding: 4px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-subtle);
            transition: color 0.15s;
            letter-spacing: 0.03em;
        }

        .collapse-all-btn:hover {
            color: var(--text-link);
        }

        .sidebar-home-item {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 7px 10px;
            font-size: 0.8rem;
            color: var(--text-secondary);
            cursor: pointer;
            border-bottom: 1px solid var(--border-subtle);
            transition: all 0.15s;
        }

        .sidebar-home-item:hover {
            color: var(--text-link);
            background: var(--bg-hover);
        }

        .sidebar-home-item i {
            color: var(--accent);
            font-size: 0.75rem;
        }

        .tree-scroll {
            overflow-y: auto;
            flex: 1;
            padding: 6px 0;
        }

        /* ── File Tree ───────────────────────────────────────────── */
        .file-tree {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .file-tree li {
            margin: 0;
        }

        .file-tree .folder-item {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            font-size: 0.8rem;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 0;
            transition: all 0.12s;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-tree .folder-item:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .file-tree .folder-item.active {
            background: rgba(59, 130, 246, 0.12);
            color: var(--text-link);
            border-left: 2px solid var(--accent);
        }

        .file-tree .folder-item .fa-folder {
            color: var(--folder-color);
            font-size: 0.75rem;
        }

        .file-tree .folder-item .caret {
            font-size: 0.6rem;
            color: var(--text-muted);
            transition: transform 0.15s;
            min-width: 10px;
        }

        .file-tree .folder-item .caret.expanded {
            transform: rotate(90deg);
        }

        .file-tree .nested {
            display: none;
            padding-left: 16px;
        }

        .file-tree .nested.active {
            display: block;
        }

        /* ── Main Content Area ───────────────────────────────────── */
        .main-content {
            flex: 1;
            background: var(--bg-deep);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── Navigation Bar ──────────────────────────────────────── */
        .navigation-bar {
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border-subtle);
            padding: 5px 12px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 2px;
        }

        .nav-links {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            font-size: 0.78rem;
            color: var(--text-secondary);
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            transition: all 0.15s;
            white-space: nowrap;
        }

        .nav-links i {
            font-size: 0.72rem;
        }

        .nav-links:hover {
            color: var(--text-link);
            background: rgba(59, 130, 246, 0.08);
        }

        .nav-links.disabled {
            color: var(--text-muted) !important;
            pointer-events: none;
            opacity: 0.45;
        }

        /* ── File Table ──────────────────────────────────────────── */
        .file-table-wrap {
            flex: 1;
            overflow-y: auto;
            padding: 0;
        }

        .file-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }

        .file-table thead th {
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border-default);
            padding: 7px 10px;
            font-weight: 600;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            position: sticky;
            top: 0;
            z-index: 10;
            white-space: nowrap;
        }

        .file-table tbody tr.file-item {
            border-bottom: 1px solid var(--border-subtle);
            transition: background 0.1s;
            cursor: pointer;
        }

        .file-table tbody tr.file-item:hover {
            background: var(--bg-hover);
        }

        .file-table tbody tr.file-item.selected {
            background: var(--bg-selected);
        }

        .file-table td {
            padding: 5px 10px;
            vertical-align: middle;
            color: var(--text-primary);
        }

        .checkbox-col {
            width: 32px;
        }

        .icon-col {
            width: 32px;
            padding-right: 0;
        }

        .file-name {
            color: var(--text-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .file-name:hover {
            color: var(--text-link);
            text-decoration: underline;
        }

        .folder-icon {
            color: var(--folder-color);
        }

        .file-size,
        .file-date,
        .file-type {
            color: var(--text-secondary);
            font-size: 0.75rem;
        }

        .file-type {
            color: var(--text-muted);
            font-size: 0.7rem;
        }

        .permissions {
            font-family: var(--font-mono);
            font-size: 0.68rem;
            color: var(--text-muted);
            letter-spacing: 0.02em;
        }

        .form-check-input {
            background-color: var(--bg-elevated);
            border-color: var(--border-default);
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: var(--accent);
            border-color: var(--accent);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }

        /* ── Footer bar ──────────────────────────────────────────── */
        .fm-footer {
            background: var(--bg-surface);
            border-top: 1px solid var(--border-subtle);
            padding: 4px 14px;
            font-size: 0.72rem;
            color: var(--text-muted);
            text-align: center;
        }

        .fm-footer a {
            color: var(--text-link);
            text-decoration: none;
        }

        .fm-footer a:hover {
            color: var(--text-link-hover);
            text-decoration: underline;
        }

        /* ── Context Menu ────────────────────────────────────────── */
        .context-menu {
            position: fixed;
            z-index: 9999;
            background: var(--bg-elevated);
            border: 1px solid var(--border-default);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            min-width: 170px;
            display: none;
            padding: 4px 0;
            backdrop-filter: blur(8px);
        }

        .context-menu-item {
            padding: 6px 14px;
            cursor: pointer;
            font-size: 0.8rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.12s;
        }

        .context-menu-item i {
            font-size: 0.72rem;
            color: var(--text-muted);
            width: 14px;
        }

        .context-menu-item:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--text-primary);
        }

        .context-menu-item:hover i {
            color: var(--text-link);
        }

        .context-menu-item.danger {
            color: #f87171;
        }

        .context-menu-item.danger i {
            color: #f87171;
        }

        .context-menu-item.danger:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        .context-menu-divider {
            border-top: 1px solid var(--border-subtle);
            margin: 3px 0;
        }

        /* ── Drag-over ───────────────────────────────────────────── */
        .drag-over {
            background: rgba(59, 130, 246, 0.05) !important;
            outline: 2px dashed var(--accent) !important;
            outline-offset: -4px;
        }

        /* ── Modals ──────────────────────────────────────────────── */
        .modal-content {
            background: var(--bg-surface);
            border: 1px solid var(--border-default);
            border-radius: var(--radius-lg);
            color: var(--text-primary);
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            background: var(--bg-elevated);
            border-bottom: 1px solid var(--border-subtle);
            padding: 10px 16px;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }

        .modal-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .modal-body {
            padding: 16px;
        }

        .modal-footer {
            background: var(--bg-elevated);
            border-top: 1px solid var(--border-subtle);
            padding: 10px 16px;
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
        }

        .form-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .form-control,
        .form-select {
            background: var(--bg-deep);
            border: 1px solid var(--border-default);
            color: var(--text-primary);
            font-size: 0.82rem;
            border-radius: var(--radius-sm);
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-control:focus,
        .form-select:focus {
            background: var(--bg-deep);
            border-color: var(--accent);
            color: var(--text-primary);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        .form-control-sm {
            font-size: 0.78rem;
            padding: 4px 8px;
        }

        .form-text,
        small {
            color: var(--text-muted);
            font-size: 0.72rem;
        }

        /* Buttons */
        .btn {
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            padding: 5px 12px;
            font-weight: 500;
            transition: all 0.15s;
        }

        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background: var(--accent-hover);
            border-color: var(--accent-hover);
            color: #fff;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }

        .btn-danger {
            background: var(--accent-danger);
            border-color: var(--accent-danger);
            color: #fff;
        }

        .btn-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
            color: #fff;
        }

        .btn-outline-secondary {
            border-color: var(--border-default);
            color: var(--text-secondary);
            background: transparent;
        }

        .btn-outline-secondary:hover {
            background: var(--bg-elevated);
            color: var(--text-primary);
            border-color: var(--border-default);
        }

        .btn-outline-light {
            border-color: var(--border-default);
            color: var(--text-secondary);
            background: transparent;
        }

        .btn-outline-light:hover {
            background: var(--bg-elevated);
            color: var(--text-primary);
            border-color: var(--border-default);
        }

        .btn-sm {
            padding: 3px 8px;
            font-size: 0.75rem;
        }

        /* Alert boxes inside modals */
        .alert {
            font-size: 0.8rem;
            border-radius: var(--radius-sm);
            padding: 8px 12px;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.25);
            color: #93c5fd;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #fca5a5;
        }

        /* Radio/Checkbox in modals */
        .form-check-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .form-check {
            margin-bottom: 3px;
        }

        /* Border in permissions boxes */
        .border {
            border-color: var(--border-default) !important;
        }

        .border.p-2 {
            background: var(--bg-deep);
            border-radius: var(--radius-sm);
        }

        /* Code editor textarea */
        .code-editor {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            line-height: 1.6;
            color: #93c5fd;
            background: var(--bg-deep);
            border: 1px solid var(--border-default);
            border-radius: var(--radius-sm);
            padding: 10px;
            resize: vertical;
            width: 100%;
        }

        .code-editor:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
            outline: none;
        }

        /* Table inside modals */
        .table {
            color: var(--text-primary);
            font-size: 0.8rem;
        }

        .table thead th {
            background: var(--bg-elevated);
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-default);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 7px 10px;
        }

        .table tbody td {
            border-bottom: 1px solid var(--border-subtle);
            padding: 6px 10px;
        }

        .table-hover tbody tr:hover {
            background: var(--bg-hover);
        }

        /* Spinner */
        .spinner-container {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .spinners {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .my-indicator {
            display: none;
        }

        .htmx-request .my-indicator {
            display: inline;
        }

        .htmx-request.my-indicator {
            display: inline;
        }

        /* CoolAlert override */
        .cool-alert-toast {
            background-color: var(--bg-elevated) !important;
            color: var(--text-primary) !important;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 48px 16px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 2rem;
            margin-bottom: 10px;
            opacity: 0.3;
        }

        .empty-state p {
            font-size: 0.82rem;
        }
    </style>
</head>

<body>

    <!-- ── Top Header ──────────────────────────────────────────── -->
    <div class="top-header">
        <div class="brand">
            <img src="logo.png" height="26" alt="The Kinsmen">
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="search-container">
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="search-input" placeholder="Search files…">
                    <button class="btn-search" id="search-btn"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <button class="btn-settings" data-bs-toggle="modal" data-bs-target="#settingsModal">
                <i class="fas fa-cog"></i> Settings
            </button>
        </div>
    </div>

    <!-- ── Main Toolbar ─────────────────────────────────────────── -->
    <div class="main-toolbar">
        <a href="#" class="header-btns" id="new-file-btn"><i class="fas fa-file"></i> New File</a>
        <a href="#" class="header-btns" id="new-folder-btn"><i class="fas fa-folder-plus"></i> New Folder</a>
        <div class="toolbar-divider"></div>
        <a href="#" class="header-btns disabled" id="copy-btn"><i class="fas fa-copy"></i> Copy</a>
        <a href="#" class="header-btns disabled" id="move-btn"><i class="fas fa-arrows-alt"></i> Move</a>
        <div class="toolbar-divider"></div>
        <a href="#" class="header-btns" id="upload-btn"><i class="fas fa-upload"></i> Upload</a>
        <a href="#" class="header-btns disabled" id="download-btn"><i class="fas fa-download"></i> Download</a>
        <div class="toolbar-divider"></div>
        <a href="#" class="header-btns disabled" id="rename-btn"><i class="fas fa-tag"></i> Rename</a>
        <a href="#" class="header-btns disabled" id="edit-btn"><i class="fas fa-code"></i> Edit</a>
        <a href="#" class="header-btns disabled" id="permissions-btn"><i class="fas fa-shield-alt"></i> Permission</a>
        <div class="toolbar-divider"></div>
        <a href="#" class="header-btns disabled" id="extract-btn"><i class="fas fa-box-open"></i> Extract</a>
        <a href="#" class="header-btns disabled" id="compress-btn"><i class="fas fa-file-archive"></i> Compress</a>
        <div class="toolbar-divider"></div>
        <a href="#" class="header-btns disabled" id="delete-btn"><i class="fas fa-trash"></i> Delete</a>
        <a href="#" class="header-btns disabled" id="restore-btn"><i class="fas fa-undo"></i> Restore</a>
        <input type="file" id="file-upload" multiple style="display:none">

        <!-- Upload progress -->
        <div class="progress ms-auto" style="width:220px" id="upload-progress">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%"
                aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                Preparing…
            </div>
        </div>
    </div>

    <!-- ── App Layout ────────────────────────────────────────────── -->
    <div class="fm-layout">

        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-path-bar">
                <button class="btn-icon" id="breadcrumb-home-btn" title="Home"><i class="fas fa-home"></i></button>
                <input type="text" class="form-control" id="breadcrumb" placeholder="/path">
                <button class="btn-icon" id="breadcrumb-search-btn">Go</button>
            </div>
            <div class="collapse-all-btn" id="collapse-all-btn">
                <i class="fas fa-compress-alt"></i> Collapse All
            </div>
            <div class="sidebar-home-item" id="sidebar-home-item">
                <i class="fas fa-home"></i>
                <span>/home/<?= htmlspecialchars($username) ?></span>
            </div>
            <div class="tree-scroll">
                <div id="directory-tree">
                    <ul class="file-tree">
                        <li style="padding: 8px 14px; font-size: 0.78rem; color: var(--text-muted);">
                            <i class="fas fa-spinner fa-spin me-2"></i> Loading…
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Main File Area -->
        <div class="main-content" id="dropzone">
            <!-- Nav bar -->
            <div class="navigation-bar">
                <a href="#" class="nav-links" id="home-btn"><i class="fas fa-home"></i> Home</a>
                <a href="#" class="nav-links" id="up-btn"><i class="fas fa-level-up-alt"></i> Up</a>
                <a href="#" class="nav-links" id="reload-btn"><i class="fas fa-sync-alt"></i> Reload</a>
                <a href="#" class="nav-links" id="select-all-btn"><i class="fas fa-check-square"></i> Select All</a>
                <a href="#" class="nav-links disabled" id="unselect-all-btn"><i class="fas fa-square"></i> Unselect</a>
                <a href="#" class="nav-links" id="view-trash-btn"><i class="fas fa-trash-alt"></i> Trash</a>
                <a href="#" class="nav-links" id="sort-btn"><i class="fas fa-sort-amount-down"></i> Sort</a>
            </div>

            <!-- File table -->
            <div class="file-table-wrap">
                <table class="file-table">
                    <thead>
                        <tr>
                            <th class="checkbox-col">
                                <input type="checkbox" class="form-check-input" id="select-all-checkbox">
                            </th>
                            <th class="icon-col"></th>
                            <th>Name</th>
                            <th>Size</th>
                            <th>Modified</th>
                            <th>Type</th>
                            <th>Permissions</th>
                        </tr>
                    </thead>
                    <tbody id="files-list">
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 32px; color: var(--text-muted);">
                                <i class="fas fa-spinner fa-spin me-2"></i> Loading files…
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="fm-footer py-2 pb-3">
                &copy; <?= date('Y') ?> <a href="https://thekinsmen.net" target="_blank">The Kinsmen</a>
                &nbsp;·&nbsp; <?= htmlspecialchars($versionInfo) ?>
                &nbsp;·&nbsp; <a href="https://github.com/JosephChuks/php-file-manager-with-code-editor"
                    target="_blank"><i class="fa-brands fa-github"></i> GitHub</a>
            </div>
        </div>
    </div>

    <!-- ── Context Menu ──────────────────────────────────────────── -->
    <div class="context-menu" id="context-menu">
        <div class="context-menu-item" id="ctx-open"><i class="fas fa-folder-open"></i> Open</div>
        <div class="context-menu-item" id="ctx-download"><i class="fas fa-download"></i> Download</div>
        <div class="context-menu-item" id="ctx-edit"><i class="fas fa-code"></i> Edit</div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item" id="ctx-copy"><i class="fas fa-copy"></i> Copy</div>
        <div class="context-menu-item" id="ctx-cut"><i class="fas fa-arrows-alt"></i> Move</div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item" id="ctx-rename"><i class="fas fa-tag"></i> Rename</div>
        <div class="context-menu-item" id="ctx-permissions"><i class="fas fa-shield-alt"></i> Permissions</div>
        <div class="context-menu-item" id="ctx-compress"><i class="fas fa-file-archive"></i> Compress</div>
        <div class="context-menu-divider"></div>
        <div class="context-menu-item danger" id="ctx-delete"><i class="fas fa-trash"></i> Delete</div>
    </div>

    <!-- ── Modals ─────────────────────────────────────────────────── -->

    <!-- Extract -->
    <div class="modal fade" id="extractModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post="" hx-trigger="submit" hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-box-open me-2 text-warning"></i>Extract Archive</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Archive: <strong id="extractFileName"
                                    style="color:var(--text-link)"></strong></label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Extract to</label>
                            <input type="text" class="form-control" id="extractPath" name="destination"
                                placeholder="Extraction path">
                            <input type="hidden" name="action" value="extract">
                            <input type="hidden" name="path" id="extractPathName">
                            <input type="hidden" name="file" id="extractFileNameValue">
                            <div class="form-text mt-1">Leave empty to extract into a folder matching the archive name
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-1"></i>
                            The destination directory will be created if it doesn't exist.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <span>Extract</span>
                            <div class="spinner-container my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- New Folder -->
    <div class="modal fade" id="newFolderModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post="" hx-trigger="submit" hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-folder-plus me-2"
                                style="color:var(--folder-color)"></i>Create New Folder</h5>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Folder Name</label>
                        <input type="text" name="name" class="form-control" id="folderName" placeholder="my-new-folder"
                            autofocus>
                        <input type="hidden" name="path" id="currenPathValue">
                        <input type="hidden" name="action" value="create_dir">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <span>Create</span>
                            <div class="spinner-container my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- New File -->
    <div class="modal fade" id="newFileModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form hx-post="" hx-trigger="submit" hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-file-plus me-2" style="color:var(--accent)"></i>Create
                            New File</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">File Name</label>
                            <input type="text" name="name" class="form-control" id="fileName" placeholder="index.php">
                            <input type="hidden" name="path" id="filePath">
                            <input type="hidden" name="action" value="create_file">
                        </div>
                        <div class="mb-1">
                            <label class="form-label">Content</label>
                        </div>
                        <textarea class="code-editor" name="content" id="fileContent" rows="12"
                            placeholder="// File contents..."></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <span>Create</span>
                            <div class="spinner-container my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Rename -->
    <div class="modal fade" id="renameModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post="" hx-trigger="submit" hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-tag me-2" style="color:var(--accent)"></i>Rename Item
                        </h5>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">New Name</label>
                        <input type="text" name="new_name" class="form-control" id="newName">
                        <input type="hidden" name="old_name" id="oldName">
                        <input type="hidden" name="path" id="renamePath">
                        <input type="hidden" name="action" value="rename">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <span>Rename</span>
                            <div class="spinner-container my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Settings -->
    <div class="modal fade" id="settingsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post="" hx-trigger="submit" hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-cog me-2" style="color:var(--accent)"></i>File Manager
                            Settings</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Timezone</label>
                            <input type="text" class="form-control" name="timezone"
                                value="<?= htmlspecialchars($config['timezone']) ?>" placeholder="Africa/Lagos"
                                required>
                            <input type="hidden" name="action" value="settings">
                            <div class="form-text">PHP timezone identifier (e.g. Africa/Lagos, UTC)</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Format</label>
                            <input type="text" class="form-control" name="dateformat"
                                value="<?= htmlspecialchars($config['date_format']) ?>" placeholder="M j Y, g:i A"
                                required>
                            <div class="form-text">PHP date() format string</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Font Size</label>
                            <input type="text" class="form-control" name="fontSize"
                                value="<?= htmlspecialchars($config['font_size']) ?>" placeholder="14px" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <span>Save Settings</span>
                            <div class="spinner-container my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Permissions -->
    <div class="modal fade" id="permissionsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post="" hx-trigger="submit" hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-shield-alt me-2" style="color:var(--accent)"></i>Change
                            Permissions</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Item: <span id="permItem"
                                    style="color:var(--text-link)"></span></label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Octal Value</label>
                            <input type="text" name="mode" class="form-control" id="permValue" placeholder="0755">
                            <input type="hidden" name="path" id="permissionPath">
                            <input type="hidden" name="item" id="permItemValue">
                            <input type="hidden" name="action" value="permissions">
                        </div>
                        <div class="row mb-2 g-2">
                            <div class="col-4">
                                <div class="border p-2" style="border-radius:var(--radius-sm)">
                                    <div class="form-text mb-1" style="color:var(--text-muted);font-weight:600">Owner
                                    </div>
                                    <div class="form-check"><input class="form-check-input perm-check" type="checkbox"
                                            id="ownerRead" data-value="400"><label class="form-check-label">Read</label>
                                    </div>
                                    <div class="form-check"><input class="form-check-input perm-check" type="checkbox"
                                            id="ownerWrite" data-value="200"><label
                                            class="form-check-label">Write</label></div>
                                    <div class="form-check"><input class="form-check-input perm-check" type="checkbox"
                                            id="ownerExec" data-value="100"><label
                                            class="form-check-label">Execute</label></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border p-2" style="border-radius:var(--radius-sm)">
                                    <div class="form-text mb-1" style="color:var(--text-muted);font-weight:600">Group
                                    </div>
                                    <div class="form-check"><input class="form-check-input perm-check" type="checkbox"
                                            id="groupRead" data-value="40"><label class="form-check-label">Read</label>
                                    </div>
                                    <div class="form-check"><input class="form-check-input perm-check" type="checkbox"
                                            id="groupWrite" data-value="20"><label
                                            class="form-check-label">Write</label></div>
                                    <div class="form-check"><input class="form-check-input perm-check" type="checkbox"
                                            id="groupExec" data-value="10"><label
                                            class="form-check-label">Execute</label></div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border p-2" style="border-radius:var(--radius-sm)">
                                    <div class="form-text mb-1" style="color:var(--text-muted);font-weight:600">Public
                                    </div>
                                    <div class="form-check"><input class="form-check-input perm-check" type="checkbox"
                                            id="publicRead" data-value="4"><label class="form-check-label">Read</label>
                                    </div>
                                    <div class="form-check"><input class="form-check-input perm-check" type="checkbox"
                                            id="publicWrite" data-value="2"><label
                                            class="form-check-label">Write</label></div>
                                    <div class="form-check"><input class="form-check-input perm-check" type="checkbox"
                                            id="publicExec" data-value="1"><label
                                            class="form-check-label">Execute</label></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <span>Apply</span>
                            <div class="spinner-container my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Compress -->
    <div class="modal fade" id="compressModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post="" hx-trigger="submit" hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-file-archive me-2"
                                style="color:var(--accent-warning)"></i>Compress Items</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Archive Name</label>
                            <input type="text" class="form-control" name="name" id="compressName" placeholder="archive">
                            <input type="hidden" name="items" id="compressItems">
                            <input type="hidden" name="path" id="compressPath">
                            <input type="hidden" name="action" value="compress">
                        </div>
                        <div class="mb-2">
                            <label class="form-label mb-2">Format</label>
                            <div class="d-flex gap-3">
                                <div class="form-check"><input class="form-check-input" type="radio" name="type"
                                        id="compressZip" value="zip" checked><label class="form-check-label"
                                        for="compressZip">ZIP</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="type"
                                        id="compressTar" value="tar"><label class="form-check-label"
                                        for="compressTar">TAR</label></div>
                                <div class="form-check"><input class="form-check-input" type="radio" name="type"
                                        id="compressGzip" value="gzip"><label class="form-check-label"
                                        for="compressGzip">GZip (single file)</label></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <span>Compress</span>
                            <div class="spinner-container my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete -->
    <div class="modal fade" id="deleteModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post="" hx-trigger="submit" hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-trash me-2"
                                style="color:var(--accent-danger)"></i>Confirm Delete</h5>
                    </div>
                    <div class="modal-body">
                        <p style="font-size:0.82rem;color:var(--text-secondary)">Are you sure you want to delete the
                            selected item(s)?</p>
                        <input type="hidden" name="items" id="deleteItems">
                        <input type="hidden" name="path" id="deletePath">
                        <input type="hidden" name="action" id="deleteAction" value="trash">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="permanentDeleteCheck">
                            <label class="form-check-label" for="permanentDeleteCheck">Permanently delete (skip
                                trash)</label>
                        </div>
                        <div class="alert alert-info" id="deleteInfoAlert">
                            <i class="fas fa-recycle me-1"></i> Items will be moved to trash and can be restored.
                        </div>
                        <div class="alert alert-danger" id="permanentDeleteAlert" style="display:none">
                            <i class="fas fa-exclamation-triangle me-1"></i> <strong>Warning:</strong> This cannot be
                            undone.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <span id="deleteButtonText">Move to Trash</span>
                            <div class="spinner-container my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Search Results -->
    <div class="modal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-search me-2" style="color:var(--accent)"></i>Search Results
                    </h5>
                </div>
                <div class="modal-body" style="padding:0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="search-results-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Path</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Modified</th>
                                </tr>
                            </thead>
                            <tbody id="search-results">
                                <tr>
                                    <td colspan="5" class="text-center" style="padding:24px;color:var(--text-muted)">
                                        Searching…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Copy / Move -->
    <div class="modal fade" id="fileOperationModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post="" hx-trigger="submit" hx-swap="none">
                    <div class="modal-header">
                        <h5 class="modal-title" id="fileOpTitle">File Operation</h5>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="fileOpType" value="copy">
                        <div class="mb-3" id="fileOpItems" style="font-size:0.8rem;color:var(--text-secondary)"></div>
                        <div class="mb-2">
                            <label class="form-label">Destination Path</label>
                            <input type="text" name="destination" class="form-control" id="destinationPath"
                                placeholder="/path/to/destination" required>
                            <input type="hidden" name="path" id="copyPath">
                            <input type="hidden" name="items" id="copyItems">
                            <div class="form-text">Current: <span id="currentPathDisplay"
                                    style="color:var(--text-link)"></span></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <span>Execute</span>
                            <div class="spinner-container my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Restore -->
    <div class="modal fade" id="restoreModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form hx-post="" hx-trigger="submit" hx-swap="none">
                    <input type="hidden" name="path" id="restorePath">
                    <input type="hidden" name="items" id="restoreItems">
                    <input type="hidden" name="action" value="restore">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-undo me-2"
                                style="color:var(--accent-success)"></i>Restore Items</h5>
                    </div>
                    <div class="modal-body">
                        <p style="font-size:0.82rem;color:var(--text-secondary)">Restore selected items to their
                            original locations?</p>
                        <ul id="restoreItemsList" class="list-group mb-3" style="font-size:0.8rem"></ul>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-1"></i> If an item exists at the destination, it will be
                            renamed automatically.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary d-flex align-items-center gap-2">
                            <span>Restore</span>
                            <div class="spinner-container my-indicator">
                                <div class="spinners"></div>
                            </div>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ── Scripts ───────────────────────────────────────────────── -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        var SERVER_ROOT_PATH = "<?php echo $config['root_path']; ?>";
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            let currentPath = '';
            let selectedItems = [];
            let fileList = [];
            let currentSort = 'name';
            let currentOrder = 'asc';
            let contextTarget = null;
            let clickedBtn = null;
            let hasResponseError = false;

            // ── File list ─────────────────────────────────────────────
            function loadFileList() {
                const tbody = document.getElementById('files-list');
                tbody.innerHTML = `<tr><td colspan="7" style="padding:32px;text-align:center;color:var(--text-muted)">
                <i class="fas fa-spinner fa-spin me-2"></i> Loading files…</td></tr>`;
                selectedItems = [];
                updateButtonStates();

                const fd = new FormData();
                fd.append('action', 'list');
                fd.append('path', currentPath);
                fd.append('sort', currentSort);
                fd.append('order', currentOrder);

                fetch(window.location.pathname, {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            fileList = data.data;
                            updateBreadcrumb(data.current_path);
                            showFiles(fileList);
                            if (data.current_path !== undefined) currentPath = data.current_path;
                        } else {
                            showAlert('Error', data.message);
                        }
                    })
                    .catch(() => showAlert('Error', 'Failed to load file list'));
            }

            function updateBreadcrumb(path) {
                document.getElementById('breadcrumb').value = path;
            }

            function getMimeType(filename) {
                const ext = filename.split('.').pop().toLowerCase();
                const map = {
                    txt: 'text/plain',
                    html: 'text/html',
                    htm: 'text/html',
                    css: 'text/css',
                    js: 'application/javascript',
                    json: 'application/json',
                    xml: 'application/xml',
                    csv: 'text/csv',
                    md: 'text/markdown',
                    jpg: 'image/jpeg',
                    jpeg: 'image/jpeg',
                    png: 'image/png',
                    gif: 'image/gif',
                    webp: 'image/webp',
                    svg: 'image/svg+xml',
                    ico: 'image/x-icon',
                    mp3: 'audio/mpeg',
                    wav: 'audio/wav',
                    mp4: 'video/mp4',
                    mov: 'video/quicktime',
                    avi: 'video/x-msvideo',
                    webm: 'video/webm',
                    pdf: 'application/pdf',
                    doc: 'application/msword',
                    docx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    xls: 'application/vnd.ms-excel',
                    xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    zip: 'application/zip',
                    tar: 'application/x-tar',
                    gz: 'application/gzip',
                    rar: 'application/vnd.rar',
                    '7z': 'application/x-7z-compressed',
                    php: 'application/x-httpd-php',
                    py: 'text/x-python',
                    sh: 'application/x-sh',
                    c: 'text/x-c',
                    cpp: 'text/x-c++',
                    java: 'text/x-java-source',
                };
                return map[ext] || 'application/octet-stream';
            }

            function showFiles(files) {
                const tbody = document.getElementById('files-list');

                if (files.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7">
                    <div class="empty-state">
                        <i class="fas fa-folder-open d-block"></i>
                        <p>This directory is empty</p>
                    </div></td></tr>`;
                    return;
                }

                tbody.innerHTML = files.map(file => `
                <tr class="file-item" data-name="${file.name}" data-type="${file.type}">
                    <td class="checkbox-col"><input type="checkbox" class="form-check-input item-check"></td>
                    <td class="icon-col">${file.name === 'public_html' ? "<i class='fas fa-globe' style='color:var(--accent)'></i>" : file.icon}</td>
                    <td><a href="#" class="file-name">${file.name}</a></td>
                    <td class="file-size">${file.size}</td>
                    <td class="file-date">${file.last_modified}</td>
                    <td class="file-type">${file.type === 'dir' ? 'directory' : getMimeType(file.name)}</td>
                    <td class="permissions">${file.permissions}</td>
                </tr>`).join('');

                document.querySelectorAll('.file-item').forEach(row => {
                    row.addEventListener('dblclick', function() {
                        const name = this.dataset.name;
                        const type = this.dataset.type;
                        if (type === 'dir') {
                            navigateTo(currentPath + '/' + name);
                        } else {
                            openFileEditor(name);
                        }
                    });

                    row.addEventListener('click', function(e) {
                        if (e.target.type === 'checkbox') return;
                        const cb = this.querySelector('.item-check');
                        cb.checked = !cb.checked;
                        cb.dispatchEvent(new Event('change'));
                    });

                    row.addEventListener('contextmenu', function(e) {
                        e.preventDefault();
                        contextTarget = {
                            name: this.dataset.name,
                            type: this.dataset.type
                        };
                        const cb = this.querySelector('.item-check');
                        if (!cb.checked) {
                            cb.checked = true;
                            cb.dispatchEvent(new Event('change'));
                        }
                        showContextMenu(e.clientX, e.clientY, this.dataset.type);
                    });

                    const cb = row.querySelector('.item-check');
                    cb.addEventListener('change', function() {
                        const name = row.dataset.name;
                        if (this.checked) {
                            row.classList.add('selected');
                            if (!selectedItems.includes(name)) selectedItems.push(name);
                        } else {
                            row.classList.remove('selected');
                            const i = selectedItems.indexOf(name);
                            if (i !== -1) selectedItems.splice(i, 1);
                        }
                        updateButtonStates();
                    });
                });
            }

            function showContextMenu(x, y, type) {
                const menu = document.getElementById('context-menu');
                menu.style.visibility = 'hidden';
                menu.style.display = 'block';
                const mw = menu.offsetWidth,
                    mh = menu.offsetHeight;
                const vw = window.innerWidth,
                    vh = window.innerHeight;
                if (x + mw > vw) x = vw - mw - 8;
                if (y + mh > vh) y = Math.max(8, y - mh);
                menu.style.left = x + 'px';
                menu.style.top = y + 'px';
                menu.style.visibility = 'visible';

                document.getElementById('ctx-edit').style.display = type === 'file' ? 'flex' : 'none';
                document.getElementById('ctx-open').style.display = type === 'dir' ? 'flex' : 'none';

                setTimeout(() => {
                    document.addEventListener('click', function close(e) {
                        if (!menu.contains(e.target)) {
                            menu.style.display = 'none';
                            document.removeEventListener('click', close);
                        }
                    });
                }, 0);
            }

            function updateButtonStates() {
                const n = selectedItems.length;
                const inTrash = currentPath === '/.trash';

                const isArchive = n === 1 && (() => {
                    const f = fileList.find(i => i.name === selectedItems[0]);
                    if (!f || f.type !== 'file') return false;
                    return ['zip', 'tar', 'gz', 'gzip', 'bz2', 'bzip2', 'rar', '7z'].includes((f
                        .extension || '').toLowerCase());
                })();
                const isFile = n === 1 && fileList.find(i => i.name === selectedItems[0])?.type === 'file';

                const states = {
                    'permissions-btn': n === 1 && !inTrash,
                    'edit-btn': n === 1 && !inTrash,
                    'rename-btn': n === 1 && !inTrash,
                    'copy-btn': n > 0 && !inTrash,
                    'move-btn': n > 0 && !inTrash,
                    'delete-btn': n > 0 && !inTrash,
                    'compress-btn': n > 0 && !inTrash,
                    'download-btn': isFile && !inTrash,
                    'extract-btn': isArchive && !inTrash,
                    'restore-btn': n > 0 && inTrash,
                    'unselect-all-btn': n > 0,
                };
                Object.entries(states).forEach(([id, on]) => {
                    document.getElementById(id)?.classList.toggle('disabled', !on);
                });
            }

            // ── Directory tree ────────────────────────────────────────
            function loadDirectoryTree() {
                const tree = document.querySelector('#directory-tree .file-tree');
                if (!tree) return;
                tree.innerHTML =
                    '<li style="padding:8px 14px;font-size:0.78rem;color:var(--text-muted)"><i class="fas fa-spinner fa-spin me-2"></i>Loading…</li>';

                const fd = new FormData();
                fd.append('action', 'tree');
                fetch(window.location.pathname, {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            tree.innerHTML = buildTreeHTML(data.data);
                            addTreeEventListeners();
                        } else {
                            tree.innerHTML =
                                '<li style="padding:8px 14px;font-size:0.78rem;color:var(--text-muted)">Failed to load tree</li>';
                        }
                    })
                    .catch(() => {
                        tree.innerHTML =
                            '<li style="padding:8px 14px;font-size:0.78rem;color:var(--text-muted)">Error loading tree</li>';
                    });
            }

            function buildTreeHTML(tree) {
                return tree.filter(i => i.type === 'dir').map(item => {
                    let p = item.path.startsWith('//') ? item.path.substring(1) : item.path;
                    let html = `<li><span class="folder-item" data-path="${p}">
                    <i class="fas fa-caret-right caret" data-expanded="false"></i>
                    <i class="fas fa-folder"></i> ${item.name}
                </span>`;
                    if (item.children && item.children.length > 0) {
                        html += `<ul class="file-tree nested">${buildTreeHTML(item.children)}</ul>`;
                    }
                    return html + '</li>';
                }).join('');
            }

            function addTreeEventListeners() {
                document.querySelectorAll('.caret').forEach(caret => {
                    caret.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const nested = this.closest('li').querySelector('.nested');
                        const expanded = this.dataset.expanded === 'true';
                        if (nested) {
                            nested.classList.toggle('active', !expanded);
                            this.classList.toggle('expanded', !expanded);
                            this.dataset.expanded = String(!expanded);
                        }
                    });
                });
                document.querySelectorAll('#directory-tree .folder-item').forEach(item => {
                    item.addEventListener('click', function(e) {
                        if (e.target.classList.contains('caret')) return;
                        const path = this.dataset.path;
                        if (path) navigateTo(path);
                    });
                });
            }

            function navigateTo(path) {
                path = path.replace(/^\/\//, '/').replace(/\/+/g, '/');
                currentPath = path;
                loadFileList();
            }

            // ── Alert ─────────────────────────────────────────────────
            function showAlert(title, message, confirm = null) {
                if (confirm !== null) {
                    CoolAlert.show({
                        title: title,
                        text: message,
                        icon: "success",
                        confirmButtonText: "Ok",
                        showCancelButton: false,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.reload();
                        }
                    });

                } else {
                    CoolAlert.show({
                        toast: true,
                        icon: title === 'Error' ? 'error' : 'success',
                        title,
                        text: message
                    });
                }


                document.querySelectorAll('.modal.show').forEach(el => {
                    const m = bootstrap.Modal.getInstance(el);
                    if (m) m.hide();
                });
            }

            // ── Permissions init ──────────────────────────────────────
            function initPermissionsModal() {
                const permValue = document.getElementById('permValue');
                const checks = document.querySelectorAll('.perm-check');
                checks.forEach(c => c.addEventListener('change', () => {
                    let v = 0;
                    checks.forEach(x => {
                        if (x.checked) v += parseInt(x.dataset.value);
                    });
                    permValue.value = '0' + v.toString(8).padStart(3, '0');
                }));
                permValue.addEventListener('input', function() {
                    const m = this.value.match(/^0([0-7]{3})$/);
                    if (m) {
                        const dec = parseInt(m[1], 8);
                        checks.forEach(c => {
                            c.checked = (dec & parseInt(c.dataset.value)) === parseInt(c.dataset
                                .value);
                        });
                    }
                });
            }

            // ── Drag & Drop ───────────────────────────────────────────
            function initDragAndDrop() {
                const dz = document.getElementById('dropzone');
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
                    e.preventDefault();
                    e.stopPropagation();
                }));
                ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, () => dz.classList.add(
                    'drag-over')));
                ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, () => dz.classList.remove(
                    'drag-over')));
                dz.addEventListener('drop', e => uploadFiles(e.dataTransfer.files));
            }

            // ── Init ──────────────────────────────────────────────────
            function init() {
                initPermissionsModal();
                initDragAndDrop();
                loadDirectoryTree();
                loadFileList();
            }

            // ── Navigation ────────────────────────────────────────────
            let pathHistory = [''];
            let historyIndex = 0;

            function navigateToPath(path) {
                currentPath = path;
                loadFileList();
            }

            document.getElementById('home-btn').addEventListener('click', e => {
                e.preventDefault();
                navigateToPath('');
            });
            document.getElementById('breadcrumb-home-btn').addEventListener('click', e => {
                e.preventDefault();
                navigateToPath('');
            });
            document.getElementById('sidebar-home-item').addEventListener('click', () => navigateToPath(''));

            document.getElementById('breadcrumb-search-btn').addEventListener('click', e => {
                e.preventDefault();
                const v = document.getElementById('breadcrumb').value.trim();
                if (v) navigateToPath(v);
            });
            document.getElementById('breadcrumb').addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    const v = e.target.value.trim();
                    if (v) navigateToPath(v);
                }
            });

            document.getElementById('up-btn').addEventListener('click', e => {
                e.preventDefault();
                if (!currentPath) return;
                navigateToPath(currentPath.split('/').slice(0, -1).join('/') || '');
            });
            document.getElementById('reload-btn').addEventListener('click', e => {
                e.preventDefault();
                loadFileList();
            });
            document.getElementById('view-trash-btn').addEventListener('click', e => {
                e.preventDefault();
                navigateToPath('/.trash');
            });
            document.getElementById('sort-btn').addEventListener('click', e => {
                e.preventDefault();
                currentOrder = currentOrder === 'asc' ? 'desc' : 'asc';
                loadFileList();
            });

            // ── Select all ────────────────────────────────────────────
            document.getElementById('select-all-checkbox').addEventListener('change', function() {
                document.querySelectorAll('.item-check').forEach(c => {
                    c.checked = this.checked;
                    c.dispatchEvent(new Event('change'));
                });
            });
            document.getElementById('select-all-btn').addEventListener('click', e => {
                e.preventDefault();
                const cb = document.getElementById('select-all-checkbox');
                cb.checked = true;
                cb.dispatchEvent(new Event('change'));
            });
            document.getElementById('unselect-all-btn').addEventListener('click', e => {
                e.preventDefault();
                if (!e.currentTarget.classList.contains('disabled')) {
                    const cb = document.getElementById('select-all-checkbox');
                    cb.checked = false;
                    cb.dispatchEvent(new Event('change'));
                }
            });

            // ── Toolbar actions ───────────────────────────────────────
            document.getElementById('new-file-btn').addEventListener('click', e => {
                e.preventDefault();
                document.getElementById('fileName').value = '';
                document.getElementById('fileContent').value = '';
                document.getElementById('filePath').value = currentPath;
                new bootstrap.Modal(document.getElementById('newFileModal')).show();
            });

            document.getElementById('new-folder-btn').addEventListener('click', e => {
                e.preventDefault();
                document.getElementById('folderName').value = '';
                document.getElementById('currenPathValue').value = currentPath;
                new bootstrap.Modal(document.getElementById('newFolderModal')).show();
            });

            document.getElementById('extract-btn').addEventListener('click', e => {
                e.preventDefault();
                const f = fileList.find(i => i.name === selectedItems[0]);
                if (f && f.type === 'file') {
                    document.getElementById('extractFileName').textContent = f.name;
                    document.getElementById('extractFileNameValue').value = f.name;
                    document.getElementById('extractPathName').value = currentPath;
                    document.getElementById('extractPath').value = currentPath + '/' + f.name.replace(
                        /\.[^/.]+$/, '');
                    new bootstrap.Modal(document.getElementById('extractModal')).show();
                } else {
                    showAlert('Error', 'Please select a file to extract');
                }
            });

            document.getElementById('rename-btn').addEventListener('click', e => {
                e.preventDefault();
                if (!e.currentTarget.classList.contains('disabled') && selectedItems.length === 1) {
                    document.getElementById('oldName').value = selectedItems[0];
                    document.getElementById('newName').value = selectedItems[0];
                    document.getElementById('renamePath').value = currentPath;
                    new bootstrap.Modal(document.getElementById('renameModal')).show();
                }
            });

            document.getElementById('permissions-btn').addEventListener('click', e => {
                e.preventDefault();
                if (!e.currentTarget.classList.contains('disabled') && selectedItems.length === 1) {
                    document.getElementById('permItem').textContent = selectedItems[0];
                    document.getElementById('permItemValue').value = selectedItems[0];
                    const item = fileList.find(i => i.name === selectedItems[0]);
                    if (item) {
                        const ps = item.permissions;
                        let o = 0,
                            g = 0,
                            w = 0;
                        if (ps.length >= 10) {
                            if (ps[1] === 'r') o += 4;
                            if (ps[2] === 'w') o += 2;
                            if (ps[3] === 'x' || ps[3] === 's') o += 1;
                            if (ps[4] === 'r') g += 4;
                            if (ps[5] === 'w') g += 2;
                            if (ps[6] === 'x' || ps[6] === 's') g += 1;
                            if (ps[7] === 'r') w += 4;
                            if (ps[8] === 'w') w += 2;
                            if (ps[9] === 'x' || ps[9] === 't') w += 1;
                        }
                        const octal = '0' + o + g + w;
                        document.getElementById('permValue').value = octal;
                        const dec = parseInt(octal.substring(1), 8);
                        document.querySelectorAll('.perm-check').forEach(c => {
                            c.checked = (dec & parseInt(c.dataset.value)) === parseInt(c.dataset
                                .value);
                        });
                    }
                    new bootstrap.Modal(document.getElementById('permissionsModal')).show();
                }
            });

            document.getElementById('compress-btn').addEventListener('click', e => {
                e.preventDefault();
                if (!e.currentTarget.classList.contains('disabled') && selectedItems.length > 0) {
                    document.getElementById('compressName').value = selectedItems.length === 1 ?
                        selectedItems[0] : 'archive_' + Math.floor(Date.now() / 1000);
                    document.getElementById('compressPath').value = currentPath;
                    document.getElementById('compressItems').value = selectedItems;
                    new bootstrap.Modal(document.getElementById('compressModal')).show();
                }
            });

            document.getElementById('delete-btn').addEventListener('click', function(e) {
                e.preventDefault();
                if (this.classList.contains('disabled') || selectedItems.length === 0) return;
                document.getElementById('deletePath').value = currentPath;
                document.getElementById('deleteItems').value = selectedItems;
                document.getElementById('deleteAction').value = 'trash';
                document.getElementById('deleteButtonText').textContent = 'Move to Trash';
                document.getElementById('deleteInfoAlert').style.display = 'block';
                document.getElementById('permanentDeleteAlert').style.display = 'none';
                document.getElementById('permanentDeleteCheck').checked = false;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            });

            document.getElementById('permanentDeleteCheck').addEventListener('change', function() {
                const perm = this.checked;
                document.getElementById('deleteAction').value = perm ? 'delete' : 'trash';
                document.getElementById('deleteInfoAlert').style.display = perm ? 'none' : 'block';
                document.getElementById('permanentDeleteAlert').style.display = perm ? 'block' : 'none';
                document.getElementById('deleteButtonText').textContent = perm ? 'Delete Permanently' :
                    'Move to Trash';
            });

            document.getElementById('restore-btn')?.addEventListener('click', function(e) {
                e.preventDefault();
                if (selectedItems.length === 0) {
                    showAlert('Error', 'Please select items to restore');
                    return;
                }
                if (!currentPath.includes('/.trash')) {
                    showAlert('Error', 'Restore is only available inside the Trash folder');
                    return;
                }
                document.getElementById('restorePath').value = currentPath;
                document.getElementById('restoreItems').value = selectedItems;
                new bootstrap.Modal(document.getElementById('restoreModal')).show();
            });

            // ── Copy / Move ───────────────────────────────────────────
            function performFileOperation(op) {
                if (selectedItems.length === 0) return;
                document.getElementById('fileOpTitle').textContent = op === 'copy' ? '📋 Copy Items' :
                    '✂️ Move Items';
                document.getElementById('fileOpType').value = op;
                document.getElementById('destinationPath').value = currentPath;
                document.getElementById('copyPath').value = currentPath;
                document.getElementById('copyItems').value = selectedItems;
                document.getElementById('fileOpItems').textContent = `Selected: ${selectedItems.length} item(s)`;
                document.getElementById('currentPathDisplay').textContent = currentPath || '/';
                new bootstrap.Modal(document.getElementById('fileOperationModal')).show();
            }

            document.getElementById('copy-btn').addEventListener('click', e => {
                e.preventDefault();
                if (!e.currentTarget.classList.contains('disabled')) performFileOperation('copy');
            });
            document.getElementById('move-btn').addEventListener('click', e => {
                e.preventDefault();
                if (!e.currentTarget.classList.contains('disabled')) performFileOperation('move');
            });

            // ── Upload ────────────────────────────────────────────────
            document.getElementById('upload-btn').addEventListener('click', e => {
                e.preventDefault();
                document.getElementById('file-upload').click();
            });
            document.getElementById('file-upload').addEventListener('change', function() {
                uploadFiles(this.files);
                this.value = '';
            });

            function uploadFiles(files) {
                if (!files.length) return;
                const fd = new FormData();
                fd.append('path', currentPath);
                for (let i = 0; i < files.length; i++) fd.append('files[]', files[i]);

                const bar = document.getElementById('upload-progress');
                const fill = bar.querySelector('.progress-bar');
                bar.style.display = 'block';
                fill.style.width = '0%';
                fill.textContent = 'Preparing…';

                const xhr = new XMLHttpRequest();
                xhr.open('POST', window.location.pathname, true);
                xhr.upload.addEventListener('progress', e => {
                    if (e.lengthComputable) {
                        const pct = Math.round(e.loaded / e.total * 100);
                        fill.style.width = pct + '%';
                        fill.textContent = pct + '%';
                        if (pct >= 100) {
                            fill.classList.remove('progress-bar-animated');
                            fill.textContent = 'Processing…';
                        }
                    }
                });
                xhr.addEventListener('load', () => {
                    setTimeout(() => {
                        bar.style.display = 'none';
                        fill.classList.add('progress-bar-animated');
                    }, 600);
                    if (xhr.status === 200) {
                        try {
                            const resp = JSON.parse(xhr.responseText);
                            if (resp.status === 'success' || resp.status === 'partial') {
                                loadFileList();
                                showAlert('Success', resp.message);
                            } else {
                                showAlert('Error', resp.message || 'Upload failed');
                            }
                        } catch {
                            showAlert('Error', 'Failed to parse server response');
                        }
                    } else {
                        showAlert('Error', 'HTTP ' + xhr.status);
                    }
                });
                xhr.addEventListener('error', () => {
                    bar.style.display = 'none';
                    showAlert('Error', 'Network error during upload');
                });
                xhr.send(fd);
            }

            // ── Download ──────────────────────────────────────────────
            document.getElementById('download-btn').addEventListener('click', e => {
                e.preventDefault();
                if (!e.currentTarget.classList.contains('disabled') && selectedItems.length === 1) {
                    window.location.href =
                        `${window.location.pathname}?action=download&path=${encodeURIComponent(currentPath)}&file=${encodeURIComponent(selectedItems[0])}`;
                }
            });

            // ── Search ────────────────────────────────────────────────
            function searchFiles() {
                const q = document.getElementById('search-input').value.trim();
                if (!q) {
                    showAlert('Error', 'Please enter a search query');
                    return;
                }

                const tbody = document.getElementById('search-results');
                tbody.innerHTML =
                    `<tr><td colspan="5" style="padding:24px;text-align:center;color:var(--text-muted)"><i class="fas fa-spinner fa-spin me-2"></i>Searching…</td></tr>`;

                const fd = new FormData();
                fd.append('action', 'search');
                fd.append('path', currentPath);
                fd.append('query', q);

                fetch(window.location.pathname, {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            if (!data.data.length) {
                                tbody.innerHTML =
                                    `<tr><td colspan="5" style="padding:24px;text-align:center;color:var(--text-muted)">No results found</td></tr>`;
                            } else {
                                tbody.innerHTML = data.data.map(r => `
                                <tr class="search-result-item" data-path="${r.path}" style="cursor:pointer">
                                    <td>${r.icon} ${r.name}</td>
                                    <td style="font-size:0.75rem;color:var(--text-muted)">${r.path}</td>
                                    <td>${r.type === 'dir' ? 'Folder' : 'File'}</td>
                                    <td>${r.size||''}</td>
                                    <td>${r.last_modified}</td>
                                </tr>`).join('');

                                document.querySelectorAll('.search-result-item').forEach(row => {
                                    row.addEventListener('dblclick', function() {
                                        const p = this.dataset.path;
                                        navigateTo(p.substring(0, p.lastIndexOf('/')));
                                        bootstrap.Modal.getInstance(document.getElementById(
                                            'searchModal')).hide();
                                    });
                                });
                            }
                        } else {
                            tbody.innerHTML =
                                `<tr><td colspan="5" style="color:var(--accent-danger)">Error: ${data.message}</td></tr>`;
                        }
                    });

                new bootstrap.Modal(document.getElementById('searchModal')).show();
            }

            document.getElementById('search-btn').addEventListener('click', e => {
                e.preventDefault();
                searchFiles();
            });
            document.getElementById('search-input').addEventListener('keypress', e => {
                if (e.key === 'Enter') searchFiles();
            });

            // ── File editor ───────────────────────────────────────────
            function openFileEditor(name) {
                const full = SERVER_ROOT_PATH + (currentPath.startsWith('/') ? currentPath : '/' + currentPath);
                const path = full.replace(/\/+$/, '') + '/' + name;
                window.open('codeEditor.php?filename=' + encodeURIComponent(path), '_blank');
            }

            document.getElementById('edit-btn').addEventListener('click', e => {
                e.preventDefault();
                if (!e.currentTarget.classList.contains('disabled') && selectedItems.length === 1)
                    openFileEditor(selectedItems[0]);
            });

            // ── Collapse tree ─────────────────────────────────────────
            document.getElementById('collapse-all-btn').addEventListener('click', () => {
                document.querySelectorAll('.caret.expanded').forEach(c => c.click());
            });

            // ── Context menu actions ──────────────────────────────────
            document.getElementById('ctx-open').addEventListener('click', () => {
                if (contextTarget?.type === 'dir') navigateTo(currentPath + '/' + contextTarget.name);
                document.getElementById('context-menu').style.display = 'none';
            });
            document.getElementById('ctx-download').addEventListener('click', () => {
                if (contextTarget) window.location.href =
                    `${window.location.pathname}?action=download&path=${encodeURIComponent(currentPath)}&file=${encodeURIComponent(contextTarget.name)}`;
                document.getElementById('context-menu').style.display = 'none';
            });
            document.getElementById('ctx-edit').addEventListener('click', () => {
                if (contextTarget?.type === 'file') openFileEditor(contextTarget.name);
                document.getElementById('context-menu').style.display = 'none';
            });
            document.getElementById('ctx-copy').addEventListener('click', () => {
                if (contextTarget) {
                    selectedItems = [contextTarget.name];
                    performFileOperation('copy');
                }
                document.getElementById('context-menu').style.display = 'none';
            });
            document.getElementById('ctx-cut').addEventListener('click', () => {
                if (contextTarget) {
                    selectedItems = [contextTarget.name];
                    performFileOperation('move');
                }
                document.getElementById('context-menu').style.display = 'none';
            });
            document.getElementById('ctx-rename').addEventListener('click', () => {
                if (contextTarget) {
                    document.getElementById('oldName').value = contextTarget.name;
                    document.getElementById('newName').value = contextTarget.name;
                    document.getElementById('renamePath').value = currentPath;
                    new bootstrap.Modal(document.getElementById('renameModal')).show();
                }
                document.getElementById('context-menu').style.display = 'none';
            });
            document.getElementById('ctx-permissions').addEventListener('click', () => {
                if (contextTarget) {
                    selectedItems = [contextTarget.name];
                    document.getElementById('permissions-btn').click();
                }
                document.getElementById('context-menu').style.display = 'none';
            });
            document.getElementById('ctx-compress').addEventListener('click', () => {
                if (contextTarget) {
                    selectedItems = [contextTarget.name];
                    document.getElementById('compress-btn').click();
                }
                document.getElementById('context-menu').style.display = 'none';
            });
            document.getElementById('ctx-delete').addEventListener('click', () => {
                if (contextTarget) {
                    selectedItems = [contextTarget.name];
                    document.getElementById('delete-btn').click();
                }
                document.getElementById('context-menu').style.display = 'none';
            });

            // ── HTMX hooks ────────────────────────────────────────────
            document.addEventListener('submit', evt => {
                evt.target.querySelectorAll("button[type='submit']").forEach(btn => {
                    if (btn.offsetParent !== null) {
                        btn.disabled = true;
                        btn.style.opacity = '0.5';
                    }
                });
            });

            document.addEventListener('htmx:afterRequest', evt => {
                if (hasResponseError) {
                    hasResponseError = false;
                    return;
                }

                document.querySelectorAll("button[type='submit']").forEach(btn => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                });

                const raw = evt.detail.xhr.response;
                if (!raw || !raw.trim()) return;

                try {
                    const data = JSON.parse(raw);
                    document.querySelectorAll('.modal.fade.show').forEach(el => {
                        (bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el)).hide();
                    });
                    if (data.status === 'success') {
                        if (data.type && data.type == 'confirm') {
                            loadFileList();
                            loadDirectoryTree();
                            showAlert('Success', data.message, 'confirm');
                        } else {
                            loadFileList();
                            loadDirectoryTree();
                            showAlert('Success', data.message);
                        }


                    } else {
                        showAlert('Error', data.message);
                    }
                } catch (e) {
                    showAlert('Error', e.message);
                }
            });

            document.body.addEventListener('htmx:responseError', e => {
                hasResponseError = true;
                document.querySelectorAll("button[type='submit']").forEach(btn => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                });
                try {
                    const data = JSON.parse(e.detail.xhr.response);
                    CoolAlert.show({
                        icon: 'error',
                        title: data.title || 'Error',
                        text: data.text || data.message || 'Something went wrong!',
                        toast: true
                    });
                } catch {
                    CoolAlert.show({
                        icon: 'error',
                        title: 'Error',
                        text: 'Request failed',
                        toast: true
                    });
                }
            });

            init();
        });
    </script>
</body>

</html>
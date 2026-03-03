<!-- KINSMEN_CUSTOM -->
<?php
if (!isset($_GET["filename"]) || !is_file($_GET["filename"])) {
    die("Invalid file specified.");
}
$filename = $_GET["filename"];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $content = $_POST["content"];
    file_put_contents($filename, $content);
    echo json_encode(["status" => "success", "message" => "File saved successfully"]);
    exit();
}
$fileContents = htmlspecialchars(file_get_contents($filename), ENT_QUOTES, "UTF-8");
$fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$basename = basename($filename);

$languageModes = [
    "html"  => "html",
    "htm"   => "html",
    "php"   => "php",
    "js"    => "javascript",
    "ts"    => "typescript",
    "jsx"   => "jsx",
    "tsx"   => "tsx",
    "css"   => "css",
    "scss"  => "scss",
    "sass"  => "sass",
    "less"  => "less",
    "json"  => "json",
    "sh"    => "sh",
    "bash"  => "sh",
    "py"    => "python",
    "sql"   => "sql",
    "md"    => "markdown",
    "xml"   => "xml",
    "yaml"  => "yaml",
    "yml"   => "yaml",
    "ini"   => "ini",
    "conf"  => "sh",
    "txt"   => "text",
    "htaccess" => "apache_conf",
];
$editorMode = $languageModes[$fileExtension] ?? "sh";

$modeOptions = [
    "html"       => "HTML",
    "php"        => "PHP",
    "javascript" => "JavaScript",
    "typescript" => "TypeScript",
    "jsx"        => "JSX",
    "css"        => "CSS",
    "scss"       => "SCSS",
    "json"       => "JSON",
    "sh"         => "Bash / Shell",
    "python"     => "Python",
    "sql"        => "SQL",
    "markdown"   => "Markdown",
    "xml"        => "XML",
    "yaml"       => "YAML",
    "ini"        => "INI",
    "text"       => "Plain Text",
    "apache_conf" => "Apache Config",
];

$fileSize = filesize($filename);
$lastModified = date("M j Y, g:i A", filemtime($filename));
$lineCount = substr_count(file_get_contents($filename), "\n") + 1;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($basename) ?> — Kinsmen Editor</title>
    <link rel="icon" href="icon.png" type="image/png">

    <!-- Ace Editor -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ace.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.12/ext-language_tools.js"></script>

    <!-- Prettier -->
    <script src="https://unpkg.com/prettier@2.7.1/standalone.js"></script>
    <script src="https://unpkg.com/prettier@2.7.1/parser-html.js"></script>
    <script src="https://unpkg.com/prettier@2.7.1/parser-babel.js"></script>
    <script src="https://unpkg.com/prettier@2.7.1/parser-postcss.js"></script>
    <script src="https://unpkg.com/@prettier/plugin-php@0.19.0/standalone.js"></script>
    <script src="https://unpkg.com/@prettier/plugin-php@0.19.0/parser-php.js"></script>
    <script src="https://unpkg.com/prettier@2.7.1/parser-markdown.js"></script>

    <!-- Font Awesome 6 (matches file manager) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        /* ── Design Tokens (matches filemanager v3.0) ────────────── */
        :root {
            --bg-deep: #0f1117;
            --bg-main: #1a1d2e;
            --bg-surface: #1e2235;
            --bg-elevated: #242840;
            --bg-hover: rgba(255, 255, 255, 0.04);

            --border-subtle: rgba(255, 255, 255, 0.06);
            --border-default: rgba(255, 255, 255, 0.10);
            --border-accent: #3b82f6;

            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --text-link: #60a5fa;

            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --accent-success: #10b981;
            --accent-danger: #ef4444;
            --accent-warning: #f59e0b;

            --radius-sm: 4px;
            --radius-md: 6px;
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.6);

            --font-mono: 'Fira Code', 'Cascadia Code', 'Courier New', monospace;
        }

        /* ── Reset ───────────────────────────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            font-size: 16px;
            background: var(--bg-deep);
            color: var(--text-primary);
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

        /* ── Top Bar ─────────────────────────────────────────────── */
        #topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 46px;
            background: var(--bg-main);
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
            z-index: 200;
            gap: 8px;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            flex: 1;
        }

        .brand-logo {
            display: flex;
            align-items: center;
        }

        .brand-logo img {
            height: 24px;
            display: block;
        }

        .file-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            background: var(--bg-elevated);
            border: 1px solid var(--border-default);
            border-radius: var(--radius-sm);
            padding: 3px 10px;
            font-size: 0.8rem;
            color: var(--text-secondary);
            min-width: 0;
            max-width: 500px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .file-badge i {
            color: var(--accent);
            font-size: 0.72rem;
            flex-shrink: 0;
        }

        .file-badge .fname {
            color: var(--text-link);
            font-weight: 600;
        }

        .unsaved-dot {
            width: 7px;
            height: 7px;
            background: var(--accent-warning);
            border-radius: 50%;
            flex-shrink: 0;
            display: none;
        }

        .unsaved-dot.visible {
            display: inline-block;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
        }

        /* ── Toolbar Selects ─────────────────────────────────────── */
        .tb-select {
            background: var(--bg-elevated);
            border: 1px solid var(--border-default);
            color: var(--text-secondary);
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            outline: none;
            transition: border-color 0.15s;
            -webkit-appearance: none;
            appearance: none;
            padding-right: 20px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2364748b'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 6px center;
        }

        .tb-select:focus,
        .tb-select:hover {
            border-color: var(--accent);
            color: var(--text-primary);
        }

        .tb-select option {
            background: var(--bg-elevated);
            color: var(--text-primary);
        }

        /* ── Toolbar Buttons ─────────────────────────────────────── */
        .tb-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 500;
            border: 1px solid var(--border-default);
            border-radius: var(--radius-sm);
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }

        .tb-btn i {
            font-size: 0.68rem;
        }

        .tb-btn:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
            border-color: var(--border-default);
        }

        .tb-btn.primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .tb-btn.primary:hover {
            background: var(--accent-hover);
            border-color: var(--accent-hover);
        }

        .tb-btn.primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .tb-btn.success {
            background: var(--accent-success);
            border-color: var(--accent-success);
            color: #fff;
        }

        .tb-btn.success:hover {
            background: #059669;
            border-color: #059669;
        }

        .tb-btn.success:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .tb-divider {
            width: 1px;
            height: 18px;
            background: var(--border-subtle);
            margin: 0 2px;
        }

        /* ── Second toolbar (sub-bar) ────────────────────────────── */
        #subbar {
            position: fixed;
            top: 46px;
            left: 0;
            right: 0;
            height: 34px;
            background: var(--bg-surface);
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            padding: 0 12px;
            gap: 4px;
            z-index: 199;
        }

        /* ── Editor Area ─────────────────────────────────────────── */
        #editor-wrap {
            position: fixed;
            top: 80px;
            bottom: 24px;
            /* status bar */
            left: 0;
            right: 0;
        }

        #editor {
            width: 100%;
            height: 100%;
            font-size: 14px;
        }

        /* ── Status Bar ──────────────────────────────────────────── */
        #statusbar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 24px;
            background: var(--bg-elevated);
            border-top: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 12px;
            font-size: 0.68rem;
            color: var(--text-muted);
            z-index: 200;
            gap: 12px;
        }

        .statusbar-left,
        .statusbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .status-item i {
            font-size: 0.6rem;
        }

        #status-saved {
            color: var(--accent-success);
            font-weight: 600;
            display: none;
        }

        #status-cursor {
            color: var(--text-secondary);
        }

        /* ── Save notification toast ─────────────────────────────── */
        #save-toast {
            position: fixed;
            top: 56px;
            right: 16px;
            background: var(--bg-elevated);
            border: 1px solid var(--accent-success);
            border-radius: var(--radius-md);
            color: var(--accent-success);
            padding: 8px 16px;
            font-size: 0.8rem;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 8px;
            z-index: 9999;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.2s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Spinner (initial load) ───────────────────────────────── */
        #spinner-overlay {
            position: fixed;
            inset: 0;
            background: var(--bg-deep);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            gap: 16px;
        }

        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid var(--border-default);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .spinner-label {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* ── Kbd shortcuts hint ──────────────────────────────────── */
        kbd {
            display: inline-block;
            background: var(--bg-deep);
            border: 1px solid var(--border-default);
            border-radius: 3px;
            padding: 1px 5px;
            font-size: 0.65rem;
            color: var(--text-muted);
            font-family: var(--font-mono);
        }
    </style>
</head>

<body>

    <!-- ── Spinner ───────────────────────────────────────────────── -->
    <div id="spinner-overlay">
        <div class="spinner"></div>
        <span class="spinner-label">Loading editor…</span>
    </div>

    <!-- ── Save Toast ────────────────────────────────────────────── -->
    <div id="save-toast">
        <i class="fas fa-check-circle"></i> File saved successfully
    </div>

    <!-- ── Top Bar ───────────────────────────────────────────────── -->
    <div id="topbar">
        <div class="topbar-left">
            <div class="brand-logo">
                <img src="logo.png" alt="Kinsmen">
            </div>
            <div class="file-badge" title="<?= htmlspecialchars($filename) ?>">
                <i class="fas fa-code"></i>
                <span class="fname"><?= htmlspecialchars($basename) ?></span>
                <span class="unsaved-dot" id="unsaved-dot" title="Unsaved changes"></span>
            </div>
            <select class="tb-select" id="mode" onchange="changeMode(this.value)" title="Language mode">
                <?php foreach ($modeOptions as $val => $label): ?>
                    <option value="<?= $val ?>" <?= $editorMode === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="topbar-right">
            <select class="tb-select" id="theme" onchange="changeTheme(this.value)" title="Editor theme">
                <option value="cobalt">Cobalt</option>
                <option value="monokai">Monokai</option>
                <option value="dracula">Dracula</option>
                <option value="tomorrow_night">Tomorrow Night</option>
                <option value="github_dark">GitHub Dark</option>
                <option value="github">GitHub Light</option>
                <option value="tomorrow">Tomorrow</option>
                <option value="kuroir">Kuroir</option>
            </select>
            <select class="tb-select" id="fontSize" onchange="changeFontSize(this.value)" title="Font size">
                <option value="12px">12px</option>
                <option value="13px">13px</option>
                <option value="14px" selected>14px</option>
                <option value="15px">15px</option>
                <option value="16px">16px</option>
                <option value="18px">18px</option>
                <option value="20px">20px</option>
            </select>
            <div class="tb-divider"></div>
            <button class="tb-btn" onclick="formatCode()" title="Format code (Shift+Alt+F)">
                <i class="fas fa-wand-magic-sparkles"></i> Format
            </button>
            <button class="tb-btn" onclick="editor.execCommand('replace')" title="Search & Replace (Ctrl+H)">
                <i class="fas fa-magnifying-glass"></i> Find
            </button>
            <button class="tb-btn" onclick="editor.undo()" title="Undo (Ctrl+Z)">
                <i class="fas fa-rotate-left"></i>
            </button>
            <button class="tb-btn" onclick="editor.redo()" title="Redo (Ctrl+Y)">
                <i class="fas fa-rotate-right"></i>
            </button>
            <div class="tb-divider"></div>
            <button class="tb-btn" onclick="copyAll()" title="Copy all to clipboard">
                <i class="fas fa-copy"></i> Copy
            </button>
            <button class="tb-btn" onclick="toggleWrap()" id="wrapBtn" title="Toggle word wrap">
                <i class="fas fa-text-width"></i> Wrap
            </button>
            <button class="tb-btn" onclick="toggleFullscreen()" title="Toggle fullscreen (F11)">
                <i class="fas fa-expand" id="fsIcon"></i>
            </button>
            <div class="tb-divider"></div>
            <button class="tb-btn success" onclick="saveFile()" id="saveBtn" title="Save (Ctrl+S)">
                <i class="fas fa-floppy-disk"></i> Save
            </button>
        </div>
    </div>

    <!-- ── Sub Bar ───────────────────────────────────────────────── -->
    <div id="subbar">
        <span style="font-size:0.72rem;color:var(--text-muted)">
            <i class="fas fa-file-code" style="color:var(--accent);margin-right:4px"></i>
            <span style="color:var(--text-secondary)"><?= htmlspecialchars($filename) ?></span>
        </span>
        <div
            style="margin-left:auto;display:flex;align-items:center;gap:10px;font-size:0.68rem;color:var(--text-muted)">
            <span><kbd>Ctrl+S</kbd> Save</span>
            <span><kbd>Ctrl+Z</kbd> Undo</span>
            <span><kbd>Ctrl+H</kbd> Find/Replace</span>
            <span><kbd>Shift+Alt+F</kbd> Format</span>
            <span><kbd>F11</kbd> Fullscreen</span>
        </div>
    </div>

    <!-- ── Editor ────────────────────────────────────────────────── -->
    <div id="editor-wrap">
        <div id="editor"><?= $fileContents ?></div>
    </div>

    <!-- ── Status Bar ────────────────────────────────────────────── -->
    <div id="statusbar">
        <div class="statusbar-left">
            <span class="status-item" id="status-cursor">
                <i class="fas fa-location-crosshairs"></i>
                Ln 1, Col 1
            </span>
            <span class="status-item" id="status-selection" style="display:none">
                <i class="fas fa-i-cursor"></i>
                <span id="sel-text"></span>
            </span>
        </div>
        <div class="statusbar-right">
            <span class="status-item" id="status-saved">
                <i class="fas fa-check-circle"></i> Saved
            </span>
            <span class="status-item">
                <i class="fas fa-code-branch"></i>
                <?= htmlspecialchars($basename) ?>
            </span>
            <span class="status-item">
                <i class="fas fa-weight-hanging"></i>
                <?= $fileSize < 1024 ? $fileSize . ' B' : round($fileSize / 1024, 1) . ' KB' ?>
            </span>
            <span class="status-item">
                <i class="fas fa-lines-leaning"></i>
                <?= number_format($lineCount) ?> lines
            </span>
            <span class="status-item">
                <i class="fas fa-clock"></i>
                Modified: <?= $lastModified ?>
            </span>
            <span class="status-item" id="status-mode">
                <i class="fas fa-circle" style="color:var(--accent);font-size:0.5rem"></i>
                <?= strtoupper($fileExtension ?: 'TXT') ?>
            </span>
        </div>
    </div>

    <script>
        // ── Init ──────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('spinner-overlay').style.display = 'none';
        });

        // ── Ace Editor ───────────────────────────────────────────────
        var editor = ace.edit("editor");
        editor.setTheme("ace/theme/cobalt");
        editor.session.setMode("ace/mode/<?= $editorMode ?>");
        editor.setOptions({
            enableBasicAutocompletion: true,
            enableSnippets: true,
            enableLiveAutocompletion: true,
            fontSize: "14px",
            wrap: false,
            highlightActiveLine: true,
            highlightSelectedWord: true,
            enableAutoIndent: true,
            enableKeyboardAccessibility: true,
            showPrintMargin: false,
            scrollPastEnd: 0.5,
            tabSize: 4,
            useSoftTabs: true,
        });

        // Remove Ace's default border
        editor.container.style.lineHeight = '1.6';

        var wrapEnabled = false;
        var isFullscreen = false;
        var hasUnsavedChanges = false;

        // ── Track unsaved changes ─────────────────────────────────────
        editor.session.on('change', function() {
            if (!hasUnsavedChanges) {
                hasUnsavedChanges = true;
                document.getElementById('unsaved-dot').classList.add('visible');
                document.title = '● <?= htmlspecialchars($basename) ?> — Kinsmen Editor';
            }
        });

        // ── Cursor / selection status ─────────────────────────────────
        editor.selection.on('changeCursor', updateCursorStatus);
        editor.selection.on('changeSelection', updateCursorStatus);

        function updateCursorStatus() {
            const pos = editor.getCursorPosition();
            document.getElementById('status-cursor').innerHTML =
                `<i class="fas fa-location-crosshairs"></i> Ln ${pos.row + 1}, Col ${pos.column + 1}`;

            const sel = editor.getSelectedText();
            const selEl = document.getElementById('status-selection');
            if (sel.length > 0) {
                document.getElementById('sel-text').textContent = `${sel.length} chars selected`;
                selEl.style.display = 'flex';
            } else {
                selEl.style.display = 'none';
            }
        }

        // ── Theme / Mode / Font ───────────────────────────────────────
        function changeTheme(theme) {
            editor.setTheme("ace/theme/" + theme);
            localStorage.setItem('ke_theme', theme);
        }

        function changeMode(mode) {
            editor.session.setMode("ace/mode/" + mode);
            document.getElementById('status-mode').innerHTML =
                `<i class="fas fa-circle" style="color:var(--accent);font-size:0.5rem"></i> ${mode.toUpperCase()}`;
            localStorage.setItem('ke_mode', mode);
        }

        function changeFontSize(size) {
            editor.setOptions({
                fontSize: size
            });
            localStorage.setItem('ke_font', size);
        }

        // ── Restore preferences ───────────────────────────────────────
        (function restorePrefs() {
            const theme = localStorage.getItem('ke_theme');
            const font = localStorage.getItem('ke_font');
            if (theme) {
                document.getElementById('theme').value = theme;
                changeTheme(theme);
            }
            if (font) {
                document.getElementById('fontSize').value = font;
                changeFontSize(font);
            }
        })();

        // ── Save ──────────────────────────────────────────────────────
        function saveFile() {
            const btn = document.getElementById('saveBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

            const content = editor.getValue();
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "codeEditor.php?filename=<?= urlencode($filename) ?>", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Save';

                    if (xhr.status === 200) {
                        hasUnsavedChanges = false;
                        document.getElementById('unsaved-dot').classList.remove('visible');
                        document.title = '<?= htmlspecialchars($basename) ?> — Kinsmen Editor';

                        // Show toast
                        const toast = document.getElementById('save-toast');
                        toast.style.display = 'flex';
                        setTimeout(() => {
                            toast.style.display = 'none';
                        }, 2000);

                        // Status bar
                        const savedEl = document.getElementById('status-saved');
                        savedEl.style.display = 'flex';
                        setTimeout(() => {
                            savedEl.style.display = 'none';
                        }, 3000);
                    } else {
                        alert('Save failed: HTTP ' + xhr.status);
                    }
                }
            };
            xhr.send("content=" + encodeURIComponent(content));
        }

        // ── Format ────────────────────────────────────────────────────
        function formatCode() {
            const code = editor.getValue();
            const mode = "<?= $fileExtension ?>";
            let formatted;
            try {
                switch (mode) {
                    case 'html':
                    case 'htm':
                        formatted = prettier.format(code, {
                            parser: "html",
                            plugins: prettierPlugins
                        });
                        break;
                    case 'js':
                    case 'jsx':
                    case 'ts':
                    case 'tsx':
                        formatted = prettier.format(code, {
                            parser: "babel",
                            plugins: prettierPlugins
                        });
                        break;
                    case 'css':
                    case 'scss':
                    case 'less':
                        formatted = prettier.format(code, {
                            parser: "css",
                            plugins: prettierPlugins
                        });
                        break;
                    case 'php':
                        formatted = prettier.format(code, {
                            parser: "php",
                            plugins: prettierPlugins
                        });
                        break;
                    case 'md':
                        formatted = prettier.format(code, {
                            parser: "markdown",
                            plugins: prettierPlugins
                        });
                        break;
                    default:
                        alert('Auto-format not available for this file type.');
                        return;
                }
                editor.setValue(formatted, -1);
            } catch (e) {
                console.error("Format error:", e);
                alert("Formatting failed: " + e.message);
            }
        }

        // ── Word wrap toggle ──────────────────────────────────────────
        function toggleWrap() {
            wrapEnabled = !wrapEnabled;
            editor.session.setUseWrapMode(wrapEnabled);
            const btn = document.getElementById('wrapBtn');
            btn.style.color = wrapEnabled ? 'var(--text-link)' : '';
            btn.style.borderColor = wrapEnabled ? 'var(--accent)' : '';
        }

        // ── Copy all ──────────────────────────────────────────────────
        function copyAll() {
            navigator.clipboard.writeText(editor.getValue()).then(() => {
                const toast = document.getElementById('save-toast');
                toast.innerHTML = '<i class="fas fa-check-circle"></i> Copied to clipboard';
                toast.style.borderColor = 'var(--accent)';
                toast.style.color = 'var(--text-link)';
                toast.style.display = 'flex';
                setTimeout(() => {
                    toast.style.display = 'none';
                    toast.innerHTML = '<i class="fas fa-check-circle"></i> File saved successfully';
                    toast.style.borderColor = 'var(--accent-success)';
                    toast.style.color = 'var(--accent-success)';
                }, 1800);
            });
        }

        // ── Fullscreen ────────────────────────────────────────────────
        function toggleFullscreen() {
            if (!isFullscreen) {
                document.documentElement.requestFullscreen?.();
            } else {
                document.exitFullscreen?.();
            }
        }
        document.addEventListener('fullscreenchange', () => {
            isFullscreen = !!document.fullscreenElement;
            document.getElementById('fsIcon').className = isFullscreen ? 'fas fa-compress' : 'fas fa-expand';
        });

        // ── Keyboard shortcuts ────────────────────────────────────────
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                saveFile();
            }
            if (e.key === 'F11') {
                e.preventDefault();
                toggleFullscreen();
            }
            if (e.shiftKey && e.altKey && e.key === 'F') {
                e.preventDefault();
                formatCode();
            }
        });

        // ── Warn before leaving with unsaved changes ──────────────────
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Leave anyway?';
            }
        });
    </script>
</body>

</html>
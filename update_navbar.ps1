$files = Get-ChildItem -Path . -Filter "*.html" -Exclude "navbar.html","footer.html"

foreach ($file in $files) {
    Write-Host "Processing $($file.Name)..."
    
    # Read the file content
    $content = Get-Content -Path $file.FullName -Raw
    
    # Create a new content variable to store the modified content
    $newContent = $content
    
    # Make sure the file includes the nav.js script
    if (-not ($newContent -match '<script src="js/nav.js"></script>')) {
        # Find where other scripts are loaded
        if ($newContent -match '<script src="[^"]*"></script>') {
            $scriptMatch = $Matches[0]
            $newContent = $newContent -replace [regex]::Escape($scriptMatch), "$scriptMatch`n  <script src=""js/nav.js""></script>"
        }
        else {
            # If no other scripts are found, add it after the closing head tag
            $newContent = $newContent -replace '</head>', "  <script src=""js/nav.js""></script>`n</head>"
        }
    }
    
    # Replace the navbar with the container div
    if ($newContent -match '(?s)<body>.*?(<nav class="navbar navbar-expand-sm navbar-dark">[\s\S]*?</nav>)') {
        $navbarSection = $Matches[1]
        $navbarContainerDiv = '<!-- 导航栏容器，将由JavaScript加载 -->
<div id="navbar-container"></div>'
        $newContent = $newContent -replace [regex]::Escape($navbarSection), $navbarContainerDiv
        Write-Host "  - Replaced navbar in $($file.Name)"
    }
    
    # Remove the login modal
    if ($newContent -match '(?s)(<div class="modal fade" id="loginModal"[\s\S]*?</div>\s*</div>\s*</div>\s*</div>)') {
        $loginModalSection = $Matches[1]
        $newContent = $newContent -replace [regex]::Escape($loginModalSection), ""
        Write-Host "  - Removed login modal from $($file.Name)"
    }
    
    # Remove the register modal
    if ($newContent -match '(?s)(<div class="modal fade" id="registerModal"[\s\S]*?</div>\s*</div>\s*</div>\s*</div>)') {
        $registerModalSection = $Matches[1]
        $newContent = $newContent -replace [regex]::Escape($registerModalSection), ""
        Write-Host "  - Removed register modal from $($file.Name)"
    }
    
    # Remove the messages modal
    if ($newContent -match '(?s)(<div class="modal fade" id="messagesModal"[\s\S]*?</div>\s*</div>\s*</div>\s*</div>)') {
        $messagesModalSection = $Matches[1]
        $newContent = $newContent -replace [regex]::Escape($messagesModalSection), ""
        Write-Host "  - Removed messages modal from $($file.Name)"
    }
    
    # Save the updated content back to the file if changes were made
    if ($newContent -ne $content) {
        Set-Content -Path $file.FullName -Value $newContent
        Write-Host "Updated $($file.Name)"
    }
    else {
        Write-Host "No changes made to $($file.Name)"
    }
}

Write-Host "All files have been processed." 
<?php

require_once 'includes/database.php';
require_once 'includes/functions.php';



if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$application_id = $_GET['id'] ?? 0;

// Get application details
$stmt = $pdo->prepare("
    SELECT a.*, c.title as course_title, c.registration_fee, c.programme_fee, c.duration
    FROM applications a
    JOIN courses c ON a.course_id = c.id
    WHERE a.id = ? AND a.user_id = ?
");
$stmt->execute([$application_id, $user_id]);
$application = $stmt->fetch();

if (!$application) {
    $_SESSION['error'] = 'Application not found!';
    header('Location: courses.php');
    exit;
}

// Check if documents already uploaded - redirect to next step
$stmt = $pdo->prepare("SELECT * FROM documents WHERE application_id = ?");
$stmt->execute([$application_id]);
$existing_docs = [];
while ($doc = $stmt->fetch()) {
    $existing_docs[$doc['document_type']] = $doc;
}

$documents_count = count($existing_docs);

// If all 3 documents uploaded, redirect to payment
if ($documents_count >= 3) {
    $_SESSION['success'] = 'All documents uploaded successfully! Proceeding to payment...';
    header('Location: payment.php?id=' . $application_id);
    exit;
}

// Generate CSRF token
$csrf_token = getCSRFToken();

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $upload_dir = 'uploads/documents/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $document_types = ['university_transcript', 'personal_statement', 'proof_of_identity'];
    $uploaded_count = 0;
    $errors = [];

    foreach ($document_types as $doc_type) {
        if (isset($_FILES[$doc_type]) && $_FILES[$doc_type]['error'] === 0) {
            $file = $_FILES[$doc_type];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

            if (!in_array($file_extension, $allowed_extensions)) {
                $errors[] = "Invalid file type for " . str_replace('_', ' ', $doc_type) . ". Allowed: PDF, DOC, DOCX, JPG, PNG";
                continue;
            }

            if ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = "File too large for " . str_replace('_', ' ', $doc_type) . " (max 5MB)";
                continue;
            }

            $file_name = $doc_type . '_' . $application_id . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Check if document already exists
                $stmt = $pdo->prepare("SELECT id FROM documents WHERE application_id = ? AND document_type = ?");
                $stmt->execute([$application_id, $doc_type]);
                $existing = $stmt->fetch();

                if ($existing) {
                    // Update existing document
                    $stmt = $pdo->prepare("UPDATE documents SET file_name = ?, file_path = ?, file_size = ?, uploaded_at = NOW() WHERE id = ?");
                    $stmt->execute([$file['name'], $file_path, $file['size'], $existing['id']]);
                } else {
                    // Insert new document
                    $stmt = $pdo->prepare("INSERT INTO documents (application_id, document_type, file_name, file_path, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$application_id, $doc_type, $file['name'], $file_path, $file['size']]);
                }
                $uploaded_count++;
            } else {
                $errors[] = "Failed to upload " . str_replace('_', ' ', $doc_type);
            }
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('. ', $errors);
    } elseif ($uploaded_count > 0) {
        // Check total documents now
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE application_id = ?");
        $stmt->execute([$application_id]);
        $total_docs = $stmt->fetchColumn();
        
        if ($total_docs >= 3) {
            $_SESSION['success'] = 'All documents uploaded successfully! Proceeding to payment...';
            header('Location: payment.php?id=' . $application_id);
            exit;
        } else {
            $_SESSION['success'] = "$uploaded_count document(s) uploaded successfully!";
        }
    } else {
        $_SESSION['error'] = 'Please select at least one document to upload';
    }
    
    header('Location: upload-documents.php?id=' . $application_id);
        exit;
    }
}

// Calculate progress
$progress = 50; // Documents step
$progress_percentage = ($documents_count / 3) * 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents - Step 3/6</title>
    <link rel="stylesheet" href="assets/css/modern-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .file-upload-area {
            border: 3px dashed #E2E8F0;
            border-radius: 12px;
            padding: 2.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #F8F9FA;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .file-upload-area:hover {
            background: #FFF5F2;
            border-color: #FF6B3D;
        }

        .file-upload-area.dragover {
            background: #FFE8DF;
            border-color: #FF6B3D;
            border-style: solid;
        }

        .file-upload-area.has-file {
            border-color: #10B981;
            background: #F0FDF4;
            border-style: solid;
        }

        .file-upload-icon {
            font-size: 3rem;
            color: #94A3B8;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .file-upload-area:hover .file-upload-icon,
        .file-upload-area.dragover .file-upload-icon {
            color: #FF6B3D;
            transform: scale(1.1);
        }

        .file-upload-area.has-file .file-upload-icon {
            color: #10B981;
        }

        .file-upload-text {
            font-size: 1.1rem;
            color: #4A5568;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .file-upload-hint {
            font-size: 0.9rem;
            color: #718096;
        }

        .file-input {
            display: none;
        }

        .file-info {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #E2E8F0;
            text-align: left;
        }

        .file-info.show {
            display: block;
        }

        .file-info-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .file-info-name {
            font-weight: 600;
            color: #1A1A1A;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .file-info-size {
            color: #718096;
            font-size: 0.9rem;
        }

        .remove-file {
            background: #EF4444;
            color: white;
            border: none;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .remove-file:hover {
            background: #DC2626;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            flex-wrap: wrap;
        }

        .step-dot {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s;
        }

        .step-dot.completed {
            background: #10B981;
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .step-dot.current {
            background: #FF6B3D;
            color: white;
            box-shadow: 0 0 0 4px rgba(255, 107, 61, 0.2);
            animation: pulse 2s infinite;
        }

        .step-dot.pending {
            background: #F1F3F5;
            color: #94A3B8;
            border: 2px solid #E2E8F0;
        }

        .step-line {
            width: 50px;
            height: 3px;
            background: #E2E8F0;
        }

        .step-line.completed {
            background: #10B981;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 0 0 4px rgba(255, 107, 61, 0.2);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(255, 107, 61, 0.1);
            }
        }

        .course-banner {
            background: linear-gradient(135deg, #FF6B3D 0%, #FF8A5C 100%);
            padding: 2rem;
            border-radius: 12px;
            color: white;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(255, 107, 61, 0.3);
        }

        .course-banner h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
        }

        .course-banner p {
            margin: 0;
            opacity: 0.95;
        }

        .document-section {
            margin-bottom: 2rem;
        }

        .document-section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1A1A1A;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .document-section-title i {
            color: #FF6B3D;
        }

        .document-section-hint {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .progress-summary {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #E2E8F0;
            margin-bottom: 2rem;
            text-align: center;
        }

        .progress-summary h3 {
            margin: 0 0 1rem 0;
            color: #1A1A1A;
        }

        .progress-bar-container {
            width: 100%;
            height: 10px;
            background: #E2E8F0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #10B981 0%, #059669 100%);
            transition: width 0.5s ease;
        }

        .progress-text {
            color: #718096;
            font-size: 0.9rem;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #FF6B3D 0%, #FF8A5C 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 61, 0.4);
            margin-top: 2rem;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 61, 0.5);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .step-indicator {
                padding: 1rem;
            }

            .step-dot {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .step-line {
                width: 30px;
            }

            .course-banner {
                padding: 1.5rem;
            }

            .file-upload-area {
                padding: 2rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
                <a href="index.php" 
                   style="display:flex; justify-content:center; align-items:center; padding:25px 0; width:100%; border-bottom:1px solid #eee;">
            
                    <img src="images/GIP GERMANY.png" 
                         alt="GIIP Germany"
                         style="width:150px; max-width:90%; height:auto; object-fit:contain; display:block;">
                         
                </a>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><span class="icon">📊</span><span>Dashboard</span></a></li>
                <li><a href="courses.php"><span class="icon">📚</span><span>Courses</span></a></li>
                <li><a href="my-application.php" class="active"><span class="icon">📋</span><span>My Applications</span></a></li>
                <li><a href="profile.php"><span class="icon">👤</span><span>Profile</span></a></li>
                <li><a href="help.php"><span class="icon">❓</span><span>Help</span></a></li>
                <li><a href="logout.php"><span class="icon">🚪</span><span>Logout</span></a></li>
            </ul>
        </aside>

        <button class="mobile-menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</button>

        <main class="main-content">
            <header class="top-bar">
                <h2>Upload Required Documents</h2>
                <div style="display: flex; gap: 1rem;">
                    <a href="my-application.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </header>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step-dot completed"><i class="fas fa-check"></i></div>
                <div class="step-line completed"></div>
                <div class="step-dot completed"><i class="fas fa-check"></i></div>
                <div class="step-line completed"></div>
                <div class="step-dot current">3</div>
                <div class="step-line"></div>
                <div class="step-dot pending">4</div>
                <div class="step-line"></div>
                <div class="step-dot pending">5</div>
                <div class="step-line"></div>
                <div class="step-dot pending">6</div>
            </div>

            <!-- Course Banner -->
            <div class="course-banner">
                <h2><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($application['course_title']); ?></h2>
                <p><i class="fas fa-clock"></i> Duration: <?php echo htmlspecialchars($application['duration']); ?> | 
                   <i class="fas fa-tag"></i> Registration Fee: €<?php echo number_format($application['registration_fee'], 0); ?> | 
                   Programme Fee: €<?php echo number_format($application['programme_fee'], 0); ?></p>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            <?php endif; ?>

            <!-- Progress Summary -->
            <div class="progress-summary">
                <h3><i class="fas fa-chart-line"></i> Upload Progress</h3>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $progress_percentage; ?>%"></div>
                </div>
                <p class="progress-text"><?php echo $documents_count; ?> of 3 documents uploaded (<?php echo round($progress_percentage); ?>%)</p>
            </div>

            <form method="POST" enctype="multipart/form-data" id="uploadForm">
    <?php csrfField(); ?>
                <!-- University Transcript -->
                <div class="document-section">
                    <h3 class="document-section-title">
                        <i class="fas fa-file-alt"></i>
                        1. University Transcript
                    </h3>
                    <p class="document-section-hint">
                        Upload your official university transcript or academic records
                    </p>
                    
                    <div class="file-upload-area <?php echo isset($existing_docs['university_transcript']) ? 'has-file' : ''; ?>" 
                         onclick="document.getElementById('university_transcript').click()"
                         ondragover="handleDragOver(event)" 
                         ondragleave="handleDragLeave(event)" 
                         ondrop="handleDrop(event, 'university_transcript')">
                        <i class="fas <?php echo isset($existing_docs['university_transcript']) ? 'fa-check-circle' : 'fa-cloud-upload-alt'; ?> file-upload-icon"></i>
                        <div class="file-upload-text">
                            <?php echo isset($existing_docs['university_transcript']) ? 'File Uploaded' : 'Click or Drag & Drop'; ?>
                        </div>
                        <div class="file-upload-hint">PDF, DOC, DOCX, JPG, PNG (Max 5MB)</div>
                        <input type="file" 
                               id="university_transcript" 
                               name="university_transcript" 
                               class="file-input"
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                               onchange="handleFileSelect(event, 'university_transcript')">
                    </div>

                    <?php if (isset($existing_docs['university_transcript'])): ?>
                        <div class="file-info show" id="info_university_transcript">
                            <div class="file-info-header">
                                <div class="file-info-name">
                                    <i class="fas fa-file-pdf"></i>
                                    <?php echo htmlspecialchars($existing_docs['university_transcript']['file_name']); ?>
                                </div>
                                <span class="file-info-size"><?php echo formatFileSize($existing_docs['university_transcript']['file_size']); ?></span>
                            </div>
                            <small style="color: #10B981;"><i class="fas fa-check"></i> Uploaded on <?php echo date('M d, Y', strtotime($existing_docs['university_transcript']['uploaded_at'])); ?></small>
                        </div>
                    <?php else: ?>
                        <div class="file-info" id="info_university_transcript"></div>
                    <?php endif; ?>
                </div>

                <!-- Personal Statement -->
                <div class="document-section">
                    <h3 class="document-section-title">
                        <i class="fas fa-file-alt"></i>
                        2. Personal Statement
                    </h3>
                    <p class="document-section-hint">
                        Upload your personal statement or motivation letter
                    </p>
                    
                    <div class="file-upload-area <?php echo isset($existing_docs['personal_statement']) ? 'has-file' : ''; ?>" 
                         onclick="document.getElementById('personal_statement').click()"
                         ondragover="handleDragOver(event)" 
                         ondragleave="handleDragLeave(event)" 
                         ondrop="handleDrop(event, 'personal_statement')">
                        <i class="fas <?php echo isset($existing_docs['personal_statement']) ? 'fa-check-circle' : 'fa-cloud-upload-alt'; ?> file-upload-icon"></i>
                        <div class="file-upload-text">
                            <?php echo isset($existing_docs['personal_statement']) ? 'File Uploaded' : 'Click or Drag & Drop'; ?>
                        </div>
                        <div class="file-upload-hint">PDF, DOC, DOCX (Max 5MB)</div>
                        <input type="file" 
                               id="personal_statement" 
                               name="personal_statement" 
                               class="file-input"
                               accept=".pdf,.doc,.docx"
                               onchange="handleFileSelect(event, 'personal_statement')">
                    </div>

                    <?php if (isset($existing_docs['personal_statement'])): ?>
                        <div class="file-info show" id="info_personal_statement">
                            <div class="file-info-header">
                                <div class="file-info-name">
                                    <i class="fas fa-file-pdf"></i>
                                    <?php echo htmlspecialchars($existing_docs['personal_statement']['file_name']); ?>
                                </div>
                                <span class="file-info-size"><?php echo formatFileSize($existing_docs['personal_statement']['file_size']); ?></span>
                            </div>
                            <small style="color: #10B981;"><i class="fas fa-check"></i> Uploaded on <?php echo date('M d, Y', strtotime($existing_docs['personal_statement']['uploaded_at'])); ?></small>
                        </div>
                    <?php else: ?>
                        <div class="file-info" id="info_personal_statement"></div>
                    <?php endif; ?>
                </div>

                <!-- Proof of Identity -->
                <div class="document-section">
                    <h3 class="document-section-title">
                        <i class="fas fa-id-card"></i>
                        3. Proof of Identity
                    </h3>
                    <p class="document-section-hint">
                        Upload a copy of your passport or national ID card
                    </p>
                    
                    <div class="file-upload-area <?php echo isset($existing_docs['proof_of_identity']) ? 'has-file' : ''; ?>" 
                         onclick="document.getElementById('proof_of_identity').click()"
                         ondragover="handleDragOver(event)" 
                         ondragleave="handleDragLeave(event)" 
                         ondrop="handleDrop(event, 'proof_of_identity')">
                        <i class="fas <?php echo isset($existing_docs['proof_of_identity']) ? 'fa-check-circle' : 'fa-cloud-upload-alt'; ?> file-upload-icon"></i>
                        <div class="file-upload-text">
                            <?php echo isset($existing_docs['proof_of_identity']) ? 'File Uploaded' : 'Click or Drag & Drop'; ?>
                        </div>
                        <div class="file-upload-hint">PDF, JPG, PNG (Max 5MB)</div>
                        <input type="file" 
                               id="proof_of_identity" 
                               name="proof_of_identity" 
                               class="file-input"
                               accept=".pdf,.jpg,.jpeg,.png"
                               onchange="handleFileSelect(event, 'proof_of_identity')">
                    </div>

                    <?php if (isset($existing_docs['proof_of_identity'])): ?>
                        <div class="file-info show" id="info_proof_of_identity">
                            <div class="file-info-header">
                                <div class="file-info-name">
                                    <i class="fas fa-file-image"></i>
                                    <?php echo htmlspecialchars($existing_docs['proof_of_identity']['file_name']); ?>
                                </div>
                                <span class="file-info-size"><?php echo formatFileSize($existing_docs['proof_of_identity']['file_size']); ?></span>
                            </div>
                            <small style="color: #10B981;"><i class="fas fa-check"></i> Uploaded on <?php echo date('M d, Y', strtotime($existing_docs['proof_of_identity']['uploaded_at'])); ?></small>
                        </div>
                    <?php else: ?>
                        <div class="file-info" id="info_proof_of_identity"></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-upload"></i> Upload Documents & Continue
                </button>
            </form>
        </main>
    </div>

    <script>
        // Drag and drop handlers
        function handleDragOver(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.add('dragover');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.remove('dragover');
        }

        function handleDrop(e, inputId) {
            e.preventDefault();
            e.stopPropagation();
            e.currentTarget.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const input = document.getElementById(inputId);
                input.files = files;
                handleFileSelect({target: input}, inputId);
            }
        }

        // File select handler
        function handleFileSelect(e, inputId) {
            const file = e.target.files[0];
            if (!file) return;

            // Validate file size
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                e.target.value = '';
                return;
            }

            // Update UI
            const uploadArea = e.target.closest('.file-upload-area');
            const icon = uploadArea.querySelector('.file-upload-icon');
            const text = uploadArea.querySelector('.file-upload-text');
            const fileInfo = document.getElementById('info_' + inputId);

            uploadArea.classList.add('has-file');
            icon.classList.remove('fa-cloud-upload-alt');
            icon.classList.add('fa-check-circle');
            text.textContent = 'File Selected';

            // Show file info
            fileInfo.innerHTML = `
                <div class="file-info-header">
                    <div class="file-info-name">
                        <i class="fas fa-file"></i>
                        ${file.name}
                    </div>
                    <span class="file-info-size">${formatBytes(file.size)}</span>
                </div>
                <small style="color: #FF6B3D;"><i class="fas fa-clock"></i> Ready to upload</small>
            `;
            fileInfo.classList.add('show');

            // Enable submit button
            document.getElementById('submitBtn').disabled = false;
        }

        // Format bytes
        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        // Check if form has files before submit
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const hasFiles = document.querySelector('input[type="file"][files]:not([files=""])');
            if (!hasFiles) {
                // Check if all files already exist
                const existingCount = document.querySelectorAll('.file-upload-area.has-file').length;
                if (existingCount < 3) {
                    e.preventDefault();
                    alert('Please select at least one document to upload');
                }
            }
        });
    </script>
</body>
</html>

<?php
// Helper function for file size formatting
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>
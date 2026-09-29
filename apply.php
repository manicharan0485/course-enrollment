<?php
require_once 'includes/database.php';
require_once 'includes/functions.php';


$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Check if editing existing application
$application_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$course_id = isset($_GET['course']) ? (int)$_GET['course'] : null;

if ($application_id) {
    $stmt = $pdo->prepare("SELECT a.*, c.* FROM applications a JOIN courses c ON c.id = a.course_id WHERE a.id = ? AND a.user_id = ?");
    $stmt->execute([$application_id, $user_id]);
    $application = $stmt->fetch();

    if (!$application) redirect('dashboard.php');
    $course_id = $application['course_id'];
} elseif ($course_id) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();

    if (!$course) redirect('courses.php');

    $stmt = $pdo->prepare("SELECT id FROM applications WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$user_id, $course_id]);
    if ($stmt->rowCount() > 0) {
        setFlashMessage('You have already applied for this course', 'warning');
        redirect('dashboard.php');
    }
} else {
    redirect('courses.php');
}

// Handle form submission
// Generate CSRF token
$csrf_token = getCSRFToken();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $address = sanitize($_POST['address']);
    $city = sanitize($_POST['city']);
    $postcode = sanitize($_POST['postcode']);
    $personal_statement = sanitize($_POST['personal_statement']);
    $present_enrolled_course = sanitize($_POST['present_enrolled_course']);
    $prev_qual_1 = sanitize($_POST['prev_qual_1']);
    $prev_qual_2 = sanitize($_POST['prev_qual_2']);
    $academic_history = sanitize($_POST['academic_history']);
    // Update user address info
    $stmt = $pdo->prepare("UPDATE users SET address = ?, city = ?, Postcode = ? WHERE id = ?");
    $stmt->execute([$address, $city, $postcode, $user_id]);
    // Insert or update application
    if (!$application_id) {
        $stmt = $pdo->prepare("
    INSERT INTO applications (user_id, course_id, personal_statement, previous_qualification_1, previous_qualification_2, academic_history, present_enrolled_course, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
");
        $stmt->execute([$user_id, $course_id, $personal_statement, $prev_qual_1, $prev_qual_2, $academic_history, $present_enrolled_course]);
        $application_id = $pdo->lastInsertId();
    } else {
        $stmt = $pdo->prepare("
            UPDATE applications 
            SET personal_statement = ?, previous_qualification_1 = ?, previous_qualification_2 = ?, academic_history = ?, present_enrolled_course = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$personal_statement, $prev_qual_1, $prev_qual_2, $academic_history, $present_enrolled_course, $application_id, $user_id]);
    }

    $document_types = ['university_transcript', 'personal_statement', 'proof_of_identity'];
    $uploaded_count = 0;

    foreach ($document_types as $doc_type) {
        if (isset($_FILES[$doc_type]) && $_FILES[$doc_type]['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadFile($_FILES[$doc_type]);
            if ($upload_result['success']) {
                $stmt = $pdo->prepare("SELECT id FROM documents WHERE application_id = ? AND document_type = ?");
                $stmt->execute([$application_id, $doc_type]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $stmt = $pdo->prepare("UPDATE documents SET file_name = ?, file_path = ?, file_size = ? WHERE id = ?");
                    $stmt->execute([$upload_result['filename'], $upload_result['path'], $_FILES[$doc_type]['size'], $existing['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO documents (application_id, document_type, file_name, file_path, file_size) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$application_id, $doc_type, $upload_result['filename'], $upload_result['path'], $_FILES[$doc_type]['size']]);
                }
                $uploaded_count++;
            }
        }
    }

    if ($uploaded_count > 0) {
        $stmt = $pdo->prepare("UPDATE applications SET status = 'documents_uploaded' WHERE id = ?");
        $stmt->execute([$application_id]);
    }

     createNotification($user_id, 'Application Updated', 'Your application has been updated successfully. Proceed to payment.', 'success', $pdo);
        setFlashMessage('Application submitted successfully! Please proceed with payment.', 'success');
        redirect('dashboard.php');
    }
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Existing documents
$existing_docs = [];
if ($application_id) {
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE application_id = ?");
    $stmt->execute([$application_id]);
    while ($doc = $stmt->fetch()) {
        $existing_docs[$doc['document_type']] = $doc;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Course Application - <?php echo SITE_NAME; ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/welcome.css">
<style>
/* Container styling */
.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 1rem;
}

/* Section headings */
h3 {
    font-weight: 600;
    margin-bottom: 1.5rem;
    font-size: 1.25rem;
    color: #222;
}

/* Form groups */
.form-group {
    margin-bottom: 1.5rem;
}

/* Labels */
.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
}

/* Inputs and textareas */
input.form-control,
textarea.form-control {
    width: 100%;
    padding: 0.6rem 0.8rem;
    font-size: 1rem;
    border: 1.5px solid #ddd;
    border-radius: 8px;
    background-color: #fafafa;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
    color: #111;
}

input.form-control:focus,
textarea.form-control:focus {
    outline: none;
    border-color: #ff6f3c; /* Accent color */
    background-color: #fff;
}

/* Grid layout for fields (two columns on larger screens) */
@media (min-width: 600px) {
    .grid-2col {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.2rem;
    }

    /* For City + Postcode where postcode is smaller */
    .grid-2col.city-postcode {
        grid-template-columns: 2fr 1fr;
    }
}

/* Responsive on small screens */
@media (max-width: 599px) {
    .grid-2col {
        display: block;
    }
}

/* Disabled inputs */
input[disabled] {
    background-color: #f3f3f3;
    color: #666;
}

/* Textarea resize control */
textarea.form-control {
    min-height: 100px;
    resize: vertical;
}
/* File upload styling */
.file-upload { border:2px dashed #ff6838; border-radius:12px; padding:1.5rem; text-align:center; cursor:pointer; transition:background-color 0.3s ease; color:#ff6838; font-weight:600; user-select:none;}
.file-upload:hover { background-color:#fff2e8; }
.file-upload-icon { font-size:2.5rem; margin-bottom:0.4rem; }
.btn-primary { background-color:#ff6838; border:none; color:white; font-weight:700; font-size:1.2rem; padding:1.2rem; border-radius:10px; cursor:pointer; transition: background-color 0.3s ease; width:100%; }
.btn-primary:hover { background-color:#e65b22; }
</style>
</head>
<body>

<div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <a href="index.php" 
                   style="display:flex; justify-content:center; align-items:center; padding:25px 0; width:100%; border-bottom:1px solid #eee;">
            
                    <img src="images/GIP GERMANY.png" 
                         alt="GIIP Germany"
                         style="width:150px; max-width:90%; height:auto; object-fit:contain; display:block;">
                         
                </a>
            </a>
            <br>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php" class="active">
                        <span class="icon">📊</span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="courses.php">
                        <span class="icon">📚</span>
                        <span>Courses</span>
                    </a>
                </li>
                <li>
                    <a href="profile.php">
                        <span class="icon">👤</span>
                        <span>Profile</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <span class="icon">🚪</span>
                        <span>Logout</span>
                    </a>
                </li>
                <li>
                    <a href="help.php">
                        <span class="icon">❓</span>
                        <span>Help & Support</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('active')">
            ☰
        </button>
        <!-- Main Content -->
        <main class="main-content" >
            <!-- Page Header -->
           <h1  style="Text-align: center; margin-bottom: 2rem;">Application for: <?php echo htmlspecialchars($course['title'] ?? 'title'); ?></h1>
            <p  style="Text-align: center; margin-bottom: 2rem;">Fill out the form below to apply for the course.</p>

<!-- Application Form -->
<div class="container">
    <?php if ($error): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
    <?php csrfField(); ?>
    <h3>Personal Details</h3>
        <div class="form-group">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" required value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
        </div>
        <div class="grid-2cols-2fr-1fr">
            <div class="form-group">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" required value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Postcode</label>
                <input type="text" name="postcode" class="form-control" required value="<?php echo htmlspecialchars($user['postcode'] ?? ''); ?>">
            </div>
        </div>

        <h3>Contact Information</h3>
        <div class="grid-2cols">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['Phone_Number'] ?? ''); ?>" disabled>
            </div>
        </div>
        <div class="grid-2cols">
            <div class="form-group">
                <label class="form-label">College</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['college'] ?? ''); ?>" disabled>
            </div>
        <div class="form-group">
                <label class="form-label">Present Enrolled Course</label>
                <input type="text" name="present_enrolled_course" class="form-control" placeholder="e.g., BSc Computer Science" value="<?php echo htmlspecialchars($application['present_enrolled_course'] ?? ''); ?>">
            </div>
        </div>

        <h3>Academic History</h3>
        <div class="grid-2cols">
            <div class="form-group">
                <label class="form-label">Previous Qualification 1</label>
                <input type="text" name="prev_qual_1" class="form-control" placeholder="e.g., BSc Computer Science" value="<?php echo htmlspecialchars($application['previous_qualification_1'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Previous Qualification 2</label>
                <input type="text" name="prev_qual_2" class="form-control" placeholder="e.g., A-Levels" value="<?php echo htmlspecialchars($application['previous_qualification_2'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Academic History (Optional)</label>
            <textarea name="academic_history" class="form-control"><?php echo htmlspecialchars($application['academic_history'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label">Personal Statement</label>
            <textarea name="personal_statement" class="form-control" required><?php echo htmlspecialchars($application['personal_statement'] ?? ''); ?></textarea>
        </div>

        <h3>Attach Documents</h3>
        <p style="color:#999; margin-bottom:1.5rem;">Upload the following required documents (PDF, DOC, DOCX, JPG, PNG - Max 5MB each)</p>

        <?php
        $doc_labels = [
            'university_transcript' => 'University Transcript',
            'personal_statement' => 'Personal Statement Document',
            'proof_of_identity' => 'Proof of Identity'
        ];
        foreach ($doc_labels as $doc_type => $label):
            $existing = isset($existing_docs[$doc_type]);
        ?>
        <div class="form-group">
            <label class="form-label">📄 <?php echo $label; ?> <?php if ($existing): ?><span style="color:#3BB143; font-size:0.9rem;">✓ Uploaded</span><?php endif; ?></label>
            <div class="file-upload" onclick="document.getElementById('<?php echo $doc_type; ?>').click()">
                <div class="file-upload-icon">⬆</div>
                <p><?php echo $existing ? 'Click to replace file' : 'Drag and drop files here or click to browse'; ?></p>
            </div>
            <input type="file" id="<?php echo $doc_type; ?>" name="<?php echo $doc_type; ?>" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="display:none;">
        </div>
        <?php endforeach; ?>

        <button type="submit" class="btn-primary">Submit Application</button>
    </form>
</div>

</body>
</html>

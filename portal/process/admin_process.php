<?php
session_start();
require_once '../includes/db_connect.php';

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php?error=アクセス権限がありません。');
    exit();
}

// --- 投稿更新処理 ---
if (isset($_POST['update_post'])) {
    $post_id = $_POST['post_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $importance = $_POST['importance'];
    $is_visible = $_POST['is_visible'];
    // Handle empty dates as NULL
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : NULL;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;

    $stmt = $conn->prepare("UPDATE posts SET title = ?, content = ?, importance = ?, is_visible = ?, start_date = ?, end_date = ? WHERE id = ?");
    $stmt->bind_param("sssisssi", $title, $content, $importance, $is_visible, $start_date, $end_date, $post_id);
    $stmt->execute();
    $stmt->close();

    // Handle new attachment upload
    if (isset($_FILES['add_attachment']) && $_FILES['add_attachment']['error'] == 0) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
        $file_name = basename($_FILES['add_attachment']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_ext)) {
            $upload_dir = '../uploads/';
            $file_path = $upload_dir . time() . '_' . $file_name;

            if (move_uploaded_file($_FILES['add_attachment']['tmp_name'], $file_path)) {
                $stmt_add_att = $conn->prepare("INSERT INTO attachments (post_id, file_name, file_path) VALUES (?, ?, ?)");
                $stmt_add_att->bind_param("iss", $post_id, $file_name, $file_path);
                $stmt_add_att->execute();
                $stmt_add_att->close();
            }
        } else {
            // Redirect with an error if the file type is not allowed
            header('Location: ../edit_post.php?id=' . $post_id . '&error=許可されていないファイル形式です。');
            exit();
        }
    }

    header('Location: ../admin.php?success=投稿を更新しました。');
    exit();
}

// --- 添付ファイル削除処理 ---
if (isset($_POST['delete_attachment'])) {
    $attachment_id = $_POST['delete_attachment'];
    $post_id = $_POST['post_id']; // Need this to redirect back

    // First, get file path to delete the actual file
    $stmt_get_path = $conn->prepare("SELECT file_path FROM attachments WHERE id = ?");
    $stmt_get_path->bind_param("i", $attachment_id);
    $stmt_get_path->execute();
    $result = $stmt_get_path->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (file_exists($row['file_path'])) {
            unlink($row['file_path']);
        }
    }
    $stmt_get_path->close();

    // Now, delete the record from the database
    $stmt_delete_att = $conn->prepare("DELETE FROM attachments WHERE id = ?");
    $stmt_delete_att->bind_param("i", $attachment_id);
    $stmt_delete_att->execute();
    $stmt_delete_att->close();

    header('Location: ../edit_post.php?id=' . $post_id . '&success=添付ファイルを削除しました。');
    exit();
}

// If no action matched, redirect to admin page
header('Location: ../admin.php');
exit();
?>

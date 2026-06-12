<?php
// PayHere එකෙන් එවන දත්ත ලබාගැනීම
$merchant_id         = $_POST['merchant_id'] ?? '';
$order_id            = $_POST['order_id'] ?? '';
$payhere_amount      = $_POST['payhere_amount'] ?? '';
$payhere_currency    = $_POST['payhere_currency'] ?? '';
$status_code         = $_POST['status_code'] ?? '';
$md5sig              = $_POST['md5sig'] ?? '';

// --- ඔයාගේ Sandbox Secret එක මෙතනට දාන්න ---
$payhere_secret = 'Mjg1MjI5ODU1OTk0NDU3Nzk2OTc4MDMzNTA1NzQ0NjcwMDgyMA=='; 

// PayHere එකෙන් එන දත්ත අපේ රහස් කේතයත් එක්ක ගැලපෙනවද (හොර එකක් නෙවෙයිද) කියලා බැලීම
$local_md5sig = strtoupper(
    md5(
        $merchant_id . 
        $order_id . 
        $payhere_amount . 
        $payhere_currency . 
        $status_code . 
        strtoupper(md5($payhere_secret))
    )
);

if (($local_md5sig === $md5sig) && ($status_code == 2) ) {
    // ගෙවීම සාර්ථකයි! (Status Code 2 කියන්නේ Success)
    require_once 'config.php'; // Database සම්බන්ධතාවය
    
    // Order එකේ Status එක 'processing' (හෝ paid) විදිහට Update කිරීම
    $stmt = $conn->prepare("UPDATE orders SET status = 'processing' WHERE order_number = ?");
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
}
?>
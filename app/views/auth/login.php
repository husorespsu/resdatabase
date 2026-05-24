<?php
/**
 * Login View
 * Uses auth layout
 * Layout: app/views/layouts/auth.php
 */
$pageTitle = 'เข้าสู่ระบบ';

ob_start();
?>

<!-- System Title -->
<div class="text-center mb-4">
    <h4 class="fw-bold mb-1" style="color: #003B6D; font-size: 1.25rem;">
        ระบบบริหารจัดการงานวิจัย
    </h4>
    <p class="text-muted mb-0" style="font-size: 0.85rem;">
        Research Management System
    </p>
</div>

<!-- Flash Messages (handled by auth layout) -->

<!-- Divider -->
<div class="text-center text-muted my-3" style="font-size: 0.85rem;">
    <span>กรุณาเข้าสู่ระบบด้วยบัญชีองค์กร</span>
</div>

<!-- Google Sign-in Button -->
<a href="<?= BASE_URL ?>/auth/google"
   class="btn d-flex align-items-center justify-content-center gap-2 w-100 py-2 px-4"
   style="
       background: #ffffff;
       border: 2px solid #dadce0;
       border-radius: 8px;
       color: #3c4043;
       font-size: 0.95rem;
       font-weight: 500;
       text-decoration: none;
       transition: background 0.2s, box-shadow 0.2s;
       box-shadow: 0 1px 3px rgba(0,0,0,0.08);
   "
   onmouseover="this.style.background='#f8f9fa'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.12)';"
   onmouseout="this.style.background='#ffffff'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)';"
   role="button">

    <!-- Google SVG Logo -->
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="22" height="22" style="flex-shrink:0;">
        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
        <path fill="none" d="M0 0h48v48H0z"/>
    </svg>

    <span>เข้าสู่ระบบด้วย Google</span>
</a>

<!-- Admin Local Login -->
<div class="mt-3">
    <div class="d-flex align-items-center gap-2 my-3">
        <hr class="flex-grow-1 m-0">
        <span class="text-muted px-2" style="font-size:0.78rem;white-space:nowrap;">หรือเข้าสู่ระบบด้วยบัญชีผู้ดูแล</span>
        <hr class="flex-grow-1 m-0">
    </div>
    <form method="POST" action="<?= BASE_URL ?>/auth/local-login" id="localLoginForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <div class="mb-2">
            <input type="text" name="username" class="form-control form-control-sm"
                   placeholder="ชื่อผู้ใช้" autocomplete="username" required>
        </div>
        <div class="mb-3">
            <input type="password" name="password" class="form-control form-control-sm"
                   placeholder="รหัสผ่าน" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn w-100 btn-sm text-white" style="background:#003B6D;">
            <i class="fas fa-sign-in-alt me-1"></i>เข้าสู่ระบบ
        </button>
    </form>
</div>

<!-- Domain Notice -->
<div class="mt-4 p-3 rounded" style="background: #E8F0FE; border-left: 4px solid #0066CC;">
    <div class="d-flex align-items-start gap-2">
        <i class="fas fa-info-circle mt-1 flex-shrink-0" style="color: #0066CC; font-size: 0.85rem;"></i>
        <div>
            <p class="mb-1 fw-semibold" style="color: #003B6D; font-size: 0.82rem;">
                โดเมนที่อนุญาต
            </p>
            <p class="mb-0 text-muted" style="font-size: 0.78rem; line-height: 1.5;">
                ระบบนี้อนุญาตเฉพาะบัญชี Google ของมหาวิทยาลัยสงขลานครินทร์
                (<strong>@psu.ac.th</strong>, <strong>@hatyai.psu.ac.th</strong>) เท่านั้น
            </p>
        </div>
    </div>
</div>

<!-- Security Note -->
<p class="text-center text-muted mt-3 mb-0" style="font-size: 0.75rem;">
    <i class="fas fa-lock me-1"></i>
    การเชื่อมต่อของคุณได้รับการเข้ารหัสและปลอดภัย
</p>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/auth.php';
?>

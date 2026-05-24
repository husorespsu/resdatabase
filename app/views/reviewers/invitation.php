<?php
/**
 * View: หนังสือเชิญผู้ทรงคุณวุฒิ (Invitation Letter Management)
 * Variables injected by Controller::render():
 * @var array  $review   — includes reviewer + proposal data (reviewer_full_name alias)
 * @var string $csrfToken
 */
$hasFile = !empty($review['invitation_file_path']);
?>

<style>
    .letter-preview {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: .5rem;
        padding: 3rem;
        font-family: 'TH SarabunNew', 'Sarabun', serif;
        font-size: 1rem;
        line-height: 1.8;
        min-height: 500px;
        position: relative;
    }
    .letter-preview::before {
        content: 'ตัวอย่างหนังสือเชิญ';
        position: absolute;
        top: 12px; right: 16px;
        background: #e9ecef; color: #6c757d;
        font-size: .75rem;
        padding: 2px 10px;
        border-radius: 1rem;
        font-family: 'Segoe UI', sans-serif;
    }
    .letter-preview .letter-date    { text-align: right; margin-bottom: 1.5rem; }
    .letter-preview .letter-body p  { text-indent: 2.5em; margin-bottom: .5rem; }
    .letter-preview .letter-sign    { text-align: center; margin-top: 3rem; }
    .info-row   { display: flex; gap: .5rem; margin-bottom: .5rem; }
    .info-label { font-weight: 600; color: #495057; min-width: 150px; }
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/research/proposals">โครงการวิจัย</a></li>
        <li class="breadcrumb-item">
            <a href="/research/proposals/<?= $review['proposal_id'] ?>">
                <?= htmlspecialchars($review['proposal_code'] ?? '') ?>
            </a>
        </li>
        <li class="breadcrumb-item">
            <a href="/research/proposals/<?= $review['proposal_id'] ?>/assign-reviewers">ผู้ทรงคุณวุฒิ</a>
        </li>
        <li class="breadcrumb-item active">หนังสือเชิญ</li>
    </ol>
</nav>

<!-- Page Header -->
<div class="p-4 rounded mb-4 text-white" style="background: linear-gradient(135deg,#003B6D,#0066CC);">
    <h4 class="mb-1 fw-bold">
        <i class="fas fa-envelope-open-text me-2"></i>หนังสือเชิญผู้ทรงคุณวุฒิ
    </h4>
    <p class="mb-0 fw-semibold opacity-90"><?= htmlspecialchars($review['reviewer_full_name'] ?? '') ?></p>
    <small class="opacity-75"><?= htmlspecialchars($review['institution'] ?? '') ?></small>
</div>

<div class="row g-4">

    <!-- Left: Info + Form -->
    <div class="col-lg-5">

        <!-- Reviewer & Proposal Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header text-white py-3" style="background:#003B6D; border-radius:.75rem .75rem 0 0;">
                <h6 class="mb-0 fw-bold"><i class="fas fa-info-circle me-2"></i>ข้อมูลการประเมิน</h6>
            </div>
            <div class="card-body">
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-user me-1 text-primary"></i>ผู้ทรงคุณวุฒิ</span>
                    <span class="fw-semibold"><?= htmlspecialchars($review['reviewer_full_name'] ?? '') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-building me-1 text-primary"></i>สังกัด</span>
                    <span><?= htmlspecialchars($review['institution'] ?? '') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-star me-1 text-primary"></i>ความเชี่ยวชาญ</span>
                    <span><?= htmlspecialchars($review['expertise'] ?? '') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-envelope me-1 text-primary"></i>อีเมล</span>
                    <span>
                        <?= !empty($review['reviewer_email'])
                            ? '<a href="mailto:' . htmlspecialchars($review['reviewer_email']) . '">' . htmlspecialchars($review['reviewer_email']) . '</a>'
                            : '-' ?>
                    </span>
                </div>
                <hr>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-file-alt me-1 text-success"></i>รหัสโครงการ</span>
                    <span class="fw-semibold"><?= htmlspecialchars($review['proposal_code'] ?? '') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-book me-1 text-success"></i>ชื่อโครงการ</span>
                    <span><?= htmlspecialchars($review['proposal_title'] ?? '') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-calendar me-1 text-success"></i>วันมอบหมาย</span>
                    <span><?= !empty($review['assigned_date']) ? date('d/m/Y', strtotime($review['assigned_date'])) : '-' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label"><i class="fas fa-clock me-1 text-warning"></i>กำหนดส่งผล</span>
                    <span class="<?= !empty($review['due_date']) && strtotime($review['due_date']) < time() ? 'text-danger fw-bold' : '' ?>">
                        <?= !empty($review['due_date']) ? date('d/m/Y', strtotime($review['due_date'])) : '-' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Invitation Form -->
        <div class="card border-0 shadow-sm">
            <div class="card-header text-white py-3" style="background:#003B6D; border-radius:.75rem .75rem 0 0;">
                <h6 class="mb-0 fw-bold"><i class="fas fa-file-signature me-2"></i>ข้อมูลหนังสือเชิญ</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/research/reviews/<?= $review['id'] ?>/invitation"
                      enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">เลขที่หนังสือ</label>
                        <input type="text" name="invitation_letter_number" class="form-control"
                               value="<?= htmlspecialchars($review['invitation_letter_number'] ?? '') ?>"
                               placeholder="เช่น ศวจ.มอ.2567/001">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">วันที่ส่งหนังสือ</label>
                        <input type="date" name="invitation_sent_date" class="form-control"
                               value="<?= htmlspecialchars($review['invitation_sent_date'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">อัปโหลดไฟล์หนังสือเชิญ (PDF/DOC)</label>
                        <input type="file" name="invitation_file" class="form-control"
                               accept=".pdf,.doc,.docx">
                        <div class="form-text">ขนาดไฟล์ไม่เกิน 10 MB</div>
                    </div>

                    <?php if ($hasFile): ?>
                    <div class="alert alert-success py-2 small">
                        <i class="fas fa-file-pdf me-1"></i>
                        มีไฟล์หนังสือเชิญอยู่แล้ว
                        <a href="<?= htmlspecialchars($review['invitation_file_path']) ?>"
                           target="_blank" class="ms-2">
                            <i class="fas fa-download me-1"></i>ดาวน์โหลด
                        </a>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn text-white" style="background:#003B6D;">
                            <i class="fas fa-save me-1"></i>บันทึกข้อมูล
                        </button>
                    </div>
                </form>

                <hr>

                <!-- Generate PDF (GET link) -->
                <a href="/research/reviews/<?= $review['id'] ?>/pdf"
                   target="_blank"
                   class="btn btn-outline-danger w-100">
                    <i class="fas fa-file-pdf me-1"></i>สร้างและดูหนังสือเชิญ (PDF)
                </a>
                <div class="form-text text-center mt-1">ระบบจะสร้าง PDF เปิดในแท็บใหม่</div>
            </div>
        </div>

    </div>

    <!-- Right: Letter Preview -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header text-white py-3" style="background:#003B6D; border-radius:.75rem .75rem 0 0;">
                <h6 class="mb-0 fw-bold"><i class="fas fa-eye me-2"></i>ตัวอย่างหนังสือเชิญ</h6>
            </div>
            <div class="card-body">
                <div class="letter-preview">
                    <div class="letter-date">
                        <strong>ที่ <?= htmlspecialchars($review['invitation_letter_number'] ?? '.../.........') ?></strong><br>
                        กองบริหารงานวิจัย มหาวิทยาลัยสงขลานครินทร์<br>
                        <?= !empty($review['invitation_sent_date'])
                            ? date('d', strtotime($review['invitation_sent_date'])) . ' เดือน ' . date('Y', strtotime($review['invitation_sent_date']))
                            : 'วันที่ .............' ?>
                    </div>

                    <p class="text-center">
                        <strong>หนังสือเชิญผู้ทรงคุณวุฒิพิจารณาโครงการวิจัย</strong>
                    </p>

                    <p>เรียน <?= htmlspecialchars($review['reviewer_full_name'] ?? '') ?><br>
                    <?= htmlspecialchars($review['institution'] ?? '') ?></p>

                    <div class="letter-body">
                        <p>
                            ด้วยมหาวิทยาลัยสงขลานครินทร์ มีความประสงค์ขอความอนุเคราะห์จากท่านในการพิจารณา
                            ตรวจประเมินโครงการวิจัย เรื่อง
                            <strong>"<?= htmlspecialchars($review['proposal_title'] ?? '') ?>"</strong>
                            (รหัสโครงการ <?= htmlspecialchars($review['proposal_code'] ?? '') ?>)
                            ซึ่งเป็นโครงการที่ยื่นขอรับทุนสนับสนุนการวิจัยจากมหาวิทยาลัยสงขลานครินทร์
                        </p>
                        <p>
                            ในการนี้ มหาวิทยาลัยฯ ใคร่ขอความกรุณาจากท่านในการพิจารณาให้ความเห็นและข้อเสนอแนะ
                            แก่โครงการดังกล่าว พร้อมส่งผลการพิจารณากลับมายังมหาวิทยาลัยฯ ภายในวันที่
                            <strong>
                                <?= !empty($review['due_date'])
                                    ? date('d/m/Y', strtotime($review['due_date']))
                                    : '...................' ?>
                            </strong>
                        </p>
                        <p>
                            จึงเรียนมาเพื่อโปรดพิจารณา และขอขอบพระคุณเป็นอย่างสูงในความอนุเคราะห์ครั้งนี้
                        </p>
                    </div>

                    <div class="letter-sign">
                        ขอแสดงความนับถือ<br><br><br>
                        (...................................................)<br>
                        ผู้อำนวยการกองบริหารงานวิจัย<br>
                        มหาวิทยาลัยสงขลานครินทร์
                    </div>
                </div>

                <?php if ($hasFile): ?>
                <div class="text-center mt-3 d-flex gap-2 justify-content-center">
                    <a href="<?= htmlspecialchars($review['invitation_file_path']) ?>"
                       target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-external-link-alt me-1"></i>เปิดไฟล์
                    </a>
                    <a href="<?= htmlspecialchars($review['invitation_file_path']) ?>"
                       download class="btn btn-outline-success">
                        <i class="fas fa-download me-1"></i>ดาวน์โหลด
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
$(document).ready(function () {
    // Live-update letter number in preview
    $('input[name="invitation_letter_number"]').on('input', function () {
        const val = $(this).val() || '.../.........';
        $('.letter-date strong').text('ที่ ' + val);
    });
});
</script>

<?php
/**
 * Hospital letterhead — only visible when printing.
 * Include this at the very start of <body> on any printable page.
 *
 * Usage:
 *   $printTitle   = 'Monthly Indicator Report';      // required
 *   $printSubtitle = 'Medicine Department — May 2026'; // optional
 *   include __DIR__ . '/../includes/letterhead.php';
 */
$printTitle    = $printTitle    ?? '';
$printSubtitle = $printSubtitle ?? '';
?>
<div id="letterhead" class="letterhead-wrap">
    <!-- Logo + Hospital Identity -->
    <div class="letterhead-top">
        <img src="<?= BASE_URL . HOSPITAL_LOGO ?>"
             alt="<?= htmlspecialchars(HOSPITAL_NAME) ?>"
             class="letterhead-logo">
        <div class="letterhead-info">
            <p class="letterhead-name-en"><?= htmlspecialchars(HOSPITAL_NAME) ?></p>
            <p class="letterhead-name-np"><?= htmlspecialchars(HOSPITAL_NAME_NEPALI) ?></p>
            <p class="letterhead-address"><?= htmlspecialchars(HOSPITAL_ADDRESS_NEPALI) ?> &nbsp;|&nbsp; <?= htmlspecialchars(HOSPITAL_ADDRESS) ?></p>
            <p class="letterhead-meta">
                <?= htmlspecialchars(HOSPITAL_PHONE) ?>
                &nbsp;·&nbsp;
                <span class="letterhead-accreditation"><?= htmlspecialchars(HOSPITAL_ACCREDITATION) ?></span>
            </p>
        </div>
    </div>
    <div class="letterhead-divider"></div>
    <?php if ($printTitle): ?>
    <div class="letterhead-report-title">
        <p class="letterhead-title-text"><?= htmlspecialchars($printTitle) ?></p>
        <?php if ($printSubtitle): ?>
        <p class="letterhead-subtitle-text"><?= htmlspecialchars($printSubtitle) ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

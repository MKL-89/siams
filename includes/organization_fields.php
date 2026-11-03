<?php
/** Organization form fields, shared by host registration and the coordinator's form. Expects $o. */
?>
<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label" for="name">Organization name</label>
        <input class="form-control" id="name" name="name" maxlength="150" value="<?= e($o['name']) ?>" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="sector">Sector</label>
        <select class="form-select" id="sector" name="sector" required><?= options(SECTORS, $o['sector']) ?></select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="county">County</label>
        <select class="form-select" id="county" name="county" required><?= options(COUNTIES, $o['county']) ?></select>
    </div>
    <div class="col-md-8">
        <label class="form-label" for="address">Physical address</label>
        <input class="form-control" id="address" name="address" maxlength="200" value="<?= e($o['address']) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="contact_email">Contact email</label>
        <input type="email" class="form-control" id="contact_email" name="contact_email" maxlength="120" value="<?= e($o['contact_email']) ?>" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="contact_phone">Contact phone</label>
        <input class="form-control" id="contact_phone" name="contact_phone" maxlength="20" value="<?= e($o['contact_phone']) ?>" required>
    </div>
</div>


<?php if (isset($_SESSION['is_switched']) && $_SESSION['is_switched']): ?>
<div class="switch-alert">
    <div style="display: flex; align-items: center; gap: 10px; background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%); color: white; padding: 10px 15px; border-radius: 8px; margin-bottom: 15px;">
        <i class="fas fa-user-secret"></i>
        <span style="flex: 1;">
            <strong>Mode "Voir comme" activé</strong>
            <br>
            <small>Vous naviguez en tant que <?php echo htmlspecialchars($_SESSION['user_name']); ?> (<?php echo $_SESSION['user_role'] === 'student' ? 'Élève' : 'Parent'; ?>)</small>
        </span>
        <a href="switch_account.php?switch_back=1" class="btn btn-sm" style="background: white; color: #6366F1;">
            <i class="fas fa-undo"></i> Revenir à moi
        </a>
    </div>
</div>
<?php endif; ?>

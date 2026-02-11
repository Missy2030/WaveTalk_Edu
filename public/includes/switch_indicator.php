<?php
// Indicateur de switch de compte
if (isset($_SESSION['is_switched']) && $_SESSION['is_switched']): 
?>
<div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); color: white; padding: 15px 20px; border-radius: 15px; box-shadow: 0 10px 25px rgba(245, 158, 11, 0.3);">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
        <i class="fas fa-exchange-alt"></i>
        <strong>Mode Parent</strong>
    </div>
    <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 10px;">
        Vous consultez le compte de votre enfant
    </div>
    <a href="../switch_account.php?switch_back=1" 
       style="display: block; background: white; color: #D97706; padding: 8px 15px; border-radius: 8px; text-decoration: none; text-align: center; font-weight: 600;">
        <i class="fas fa-arrow-left"></i> Retour à mon compte
    </a>
</div>
<?php endif; ?>
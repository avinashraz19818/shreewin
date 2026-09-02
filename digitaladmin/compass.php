		<?php
			$chkserial = mysqli_query($conn,"select * from `nirvahaka_shonu` where `unohs`='".$_SESSION['unohs']."'");
			$salu = mysqli_fetch_array($chkserial);
			$dashboard = $salu['dashboard'];
			$wingomanager = $salu['wingomanager'];
			$k3manager = $salu['k3manager'];
			$d5manager = $salu['5dmanager'];
			$finance = $salu['finance'];
			$managegame = $salu['managegame'];
            $support = $salu['support'];
            $setting = $salu['setting'];
            $agents = $salu['agents'];
            $marketing = $salu['marketing'];
            $admins= $salu['admins'];
            $teachers= $salu['teachers'];
            
            
            $subRoles = [];
$adminId = $_SESSION['unohs'];

$subQuery = mysqli_query($conn, "SELECT module, submodule FROM admin_roles WHERE admin_id = '$adminId' AND allowed = 1");
while ($row = mysqli_fetch_assoc($subQuery)) {
    $module = $row['module'];
    $sub = $row['submodule'];

    if (!isset($subRoles[$module])) {
        $subRoles[$module] = [];
    }

    $subRoles[$module][] = $sub;
}




		?>
		

		
		
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
		
		
		
		

		<head>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<ul class="nav">
  <li class="nav-item">
    <a class="nav-link" href="dashboard.php">
      <i class="material-icons menu-icon">dashboard</i>
      <span class="menu-title">Dashboard</span>
    </a>

          </li>
          
          <li class="nav-item">
  <a class="nav-link" href="api-panel.php" style="display:flex;align-items:center;">
    <img src="https://gamblly.com/logo.png" style="width:100%;max-width:140px;display:block;margin:0 auto;">
  </a>
</li>
<li class="nav-item">
  <a class="nav-link" href="Chub94/home-games.php" style="display:flex;align-items:center;">
    <i class="material-icons menu-icon" style="margin-right:8px;">sports_esports</i>
    <span>Manage Game</span>
  </a>
</li>
		  <?php if ($wingomanager == 1 && isset($subRoles['wingo'])) { ?>
<li class="nav-item">
  <a class="nav-link d-flex align-items-center" data-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
    <i class="material-icons" style="font-size: 22px; line-height: 1; margin-right: 8px;">sports_esports</i>
    <span class="menu-title">WinGo Manager</span>
    <i class="menu-arrow ml-auto"></i>
  </a>

  <div class="collapse" id="ui-basic">
    <ul class="nav flex-column sub-menu">

      <?php if (in_array('30s', $subRoles['wingo'])) { ?>
        <li class="nav-item"> <a class="nav-link" href="wingo10min.php">WinGo 30 sec</a></li>
      <?php } ?>

      <?php if (in_array('1min', $subRoles['wingo'])) { ?>
        <li class="nav-item"> <a class="nav-link" href="wingo1min.php">WinGo 1 Min</a></li>
      <?php } ?>

      <?php if (in_array('3min', $subRoles['wingo'])) { ?>
        <li class="nav-item"> <a class="nav-link" href="wingo3min.php">WinGo 3 Min</a></li>
      <?php } ?>

      <?php if (in_array('5min', $subRoles['wingo'])) { ?>
        <li class="nav-item"> <a class="nav-link" href="wingo5min.php">WinGo 5 Min</a></li>
      <?php } ?>

    </ul>
  </div>
</li>


		  <?php if ($k3manager == 1 && isset($subRoles['k3'])) { ?>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center" data-toggle="collapse" href="#ui-basic-1" aria-expanded="false" aria-controls="ui-basic-1">
      <i class="material-icons menu-icon" style="font-size: 22px; line-height: 1; margin-right: 8px;">sports_score</i>
      <span class="menu-title">K3 Manager</span>
      <i class="menu-arrow ml-auto"></i>
    </a>
    <div class="collapse" id="ui-basic-1">
      <ul class="nav flex-column sub-menu">
        <?php if (in_array('1min', $subRoles['k3'])) { ?>
          <li class="nav-item"><a class="nav-link" href="k31min.php">K3 1 Min</a></li>
        <?php } ?>
        <?php if (in_array('3min', $subRoles['k3'])) { ?>
          <li class="nav-item"><a class="nav-link" href="k33min.php">K3 3 Min</a></li>
        <?php } ?>
        <?php if (in_array('5min', $subRoles['k3'])) { ?>
          <li class="nav-item"><a class="nav-link" href="k35min.php">K3 5 Min</a></li>
        <?php } ?>
        <?php if (in_array('10min', $subRoles['k3'])) { ?>
          <li class="nav-item"><a class="nav-link" href="k310min.php">K3 10 Min</a></li>
        <?php } ?>
      </ul>
    </div>
  </li>
<?php } ?>


		  <?php if ($d5manager == 1 && isset($subRoles['5d'])) { ?>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center" data-toggle="collapse" href="#ui-basic-2" aria-expanded="false" aria-controls="ui-basic-2">
      <i class="material-icons menu-icon" style="font-size: 22px; line-height: 1; margin-right: 8px;">casino</i>
      <span class="menu-title">5D Manager</span>
      <i class="menu-arrow ml-auto"></i>
    </a>
    <div class="collapse" id="ui-basic-2">
      <ul class="nav flex-column sub-menu">
        <?php if (in_array('1min', $subRoles['5d'])) { ?>
          <li class="nav-item"><a class="nav-link" href="5d1min.php">5D 1 Min</a></li>
        <?php } ?>
        <?php if (in_array('3min', $subRoles['5d'])) { ?>
          <li class="nav-item"><a class="nav-link" href="5d3min.php">5D 3 Min</a></li>
        <?php } ?>
        <?php if (in_array('5min', $subRoles['5d'])) { ?>
          <li class="nav-item"><a class="nav-link" href="5d5min.php">5D 5 Min</a></li>
        <?php } ?>
        <?php if (in_array('10min', $subRoles['5d'])) { ?>
          <li class="nav-item"><a class="nav-link" href="5d10min.php">5D 10 Min</a></li>
        <?php } ?>
      </ul>
    </div>
  </li>
<?php } ?>

          
<?php if ($admins == 1 && isset($subRoles['admins'])) { ?>
  <li class="nav-item">
    <a class="nav-link" data-toggle="collapse" href="#ui-basic-9" aria-expanded="false" aria-controls="ui-basic-9">
      <i class="material-icons menu-icon">admin_panel_settings</i>
      <span class="menu-title">Admins Manager</span>
      <i class="menu-arrow"></i>
    </a>
    <div class="collapse" id="ui-basic-9">
      <ul class="nav flex-column sub-menu">
        <?php if (in_array('password', $subRoles['admins'])) { ?>
          <li class="nav-item"> <a class="nav-link" href="adminpass.php">Admin Password</a></li>
        <?php } ?>
        
         <?php if (in_array('adminsroles', $subRoles['admins'])) { ?>
          <li class="nav-item"> <a class="nav-link" href="adminsroles"> Admin Roles</a></li>
        <?php } ?>
        
        
        <?php if (in_array('addadmin', $subRoles['admins'])) { ?>
          <li class="nav-item"> <a class="nav-link" href="addadmin.php">Add Admin</a></li>
        <?php } ?>
        
      </ul>
    </div>
  </li>
  
  
  
  
<?php } ?>




         
          
<?php
}
if ($finance == 1 && isset($subRoles['finance'])) {
?>
<li class="nav-item">
  <a class="nav-link d-flex align-items-center" data-toggle="collapse" href="#ui-basic-3" aria-expanded="false" aria-controls="ui-basic-3">
    <i class="material-icons menu-icon" style="font-size: 22px; line-height: 1; margin-right: 8px;">account_balance</i>
    <span class="menu-title">Finance</span>
    <i class="menu-arrow ml-auto"></i>
  </a>

  <div class="collapse" id="ui-basic-3">
    <ul class="nav flex-column sub-menu">
      <?php if (in_array('addupi', $subRoles['finance'])) { ?>
        <li class="nav-item"><a class="nav-link" href="addupi.php">Manage Deposit</a></li>
      <?php } ?>
       <?php if (in_array('addupi', $subRoles['finance'])) { ?>
        <li class="nav-item"><a class="nav-link" href="usdt_update.php">Usdt Manage</a></li>
      <?php } ?>
      <?php if (in_array('deposit_update', $subRoles['finance'])) { ?>
        <li class="nav-item"><a class="nav-link" href="deposit_update.php">Deposit Update</a></li>
      <?php } ?>
      <?php if (in_array('manage_withdraw', $subRoles['finance'])) { ?>
        <li class="nav-item"><a class="nav-link" href="manage_withdraw.php">Withdraw Apply</a></li>
      <?php } ?>
      <?php if (in_array('withdraw_accept_list', $subRoles['finance'])) { ?>
        <li class="nav-item"><a class="nav-link" href="withdraw_accept_list.php">Withdraw Sent</a></li>
      <?php } ?>
      <?php if (in_array('withdraw_reject_list', $subRoles['finance'])) { ?>
        <li class="nav-item"><a class="nav-link" href="withdraw_reject_list.php">Withdraw Reject</a></li>
      <?php } ?>
      <?php if (in_array('update_with_pay', $subRoles['finance'])) { ?>
        <li class="nav-item"><a class="nav-link" href="update_with_pay.php">Update Gateway</a></li>
      <?php } ?>
       <?php if (in_array('manage_paymentgateway', $subRoles['setting'])) { ?> 
        <li class="nav-item"><a class="nav-link" href="manage_paymentgateway.php">Payment Gateway Setting</a></li> 
      <?php } ?>
             <?php if (in_array('manage_paymentgateway', $subRoles['setting'])) { ?> 
        <li class="nav-item"><a class="nav-link" href="withdraw_rules.php">Withdraw Rules</a></li> 
      <?php } ?>
    </ul>
  </div>
</li>

<?php } ?>

          
          
        <?php
if ($support == 1 && isset($subRoles['support'])) {
?>
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center" data-toggle="collapse" href="#ui-basic-5" aria-expanded="false" aria-controls="ui-basic-5">
      <i class="material-icons menu-icon" style="font-size: 22px; line-height: 1; margin-right: 8px;">support_agent</i>
      <span class="menu-title">Support</span>
      <i class="menu-arrow ml-auto"></i>
    </a>

    <div class="collapse" id="ui-basic-5">
      <ul class="nav flex-column sub-menu">
        <?php if (in_array('deposite', $subRoles['support'])) { ?>
          <li class="nav-item"><a class="nav-link" href="deposite.php">Deposite Problem</a></li>
        <?php } ?>
        <?php if (in_array('withprob', $subRoles['support'])) { ?>
          <li class="nav-item"><a class="nav-link" href="withprob.php">Withdrawal Problem</a></li>
        <?php } ?>
        <?php if (in_array('ifscm', $subRoles['support'])) { ?>
          <li class="nav-item"><a class="nav-link" href="ifscm.php">IFSC Modification</a></li>
        <?php } ?>
        <?php if (in_array('bankm', $subRoles['support'])) { ?>
          <li class="nav-item"><a class="nav-link" href="bankm.php">Bank Modification</a></li>
        <?php } ?>
        <?php if (in_array('gamep', $subRoles['support'])) { ?>
          <li class="nav-item"><a class="nav-link" href="gamep.php">Game Problem</a></li>
        <?php } ?>
      </ul>
    </div>
  </li>




          
          
          
		  <?php
}
if ($managegame == 1 && isset($subRoles['managegame'])) {
?>
<li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#ui-basic-4" aria-expanded="false" aria-controls="ui-basic-4">
    <i class="icon-head menu-icon"></i>
    <span class="menu-title">Manage Users</span>
    <i class="menu-arrow"></i>
  </a>
  <div class="collapse" id="ui-basic-4">
    <ul class="nav flex-column sub-menu">
      <?php if (in_array('userbonus', $subRoles['managegame'])) { ?>
        <li class="nav-item"> <a class="nav-link" href="userbonus.php">Bonus Manage</a></li>
      <?php } ?>
      
        <ul class="nav flex-column sub-menu">
    <li class="nav-item"> <a class="nav-link" href="Chub94/balance_detuction.php">Balance Detuction</a></li>
        </ul>
              
      <ul class="nav flex-column sub-menu">
          <li class="nav-item"> <a class="nav-link" href="Chub94/users_deposit_downline.php">Users Downline</a></li>
     </ul>
     <ul class="nav flex-column sub-menu">
    <li class="nav-item"> <a class="nav-link" href="Chub94/fully_detailed_subdata.php">Subordinate Data</a></li>
     </ul>
				
	<ul class="nav flex-column sub-menu">
             <li class="nav-item"> <a class="nav-link" href="Chub94/kacha_chitha_of_user.php">Users Detail History</a></li>
        </ul>
      <?php if (in_array('manage_bankcard', $subRoles['managegame'])) { ?>
        <li class="nav-item"> <a class="nav-link" href="manage_bankcard.php">Modify Bank Details</a></li>
      <?php } ?>
      <?php if (in_array('autobanuser', $subRoles['managegame'])) { ?>
        <li class="nav-item"> <a class="nav-link" href="autobanuser.php">Check Same IP</a></li>
      <?php } ?>
      <?php if (in_array('banbybet', $subRoles['managegame'])) { ?>
        <li class="nav-item"> <a class="nav-link" href="banbybet.php">Ban Illigal Users</a></li>
      <?php } ?>
      <?php if (in_array('manage_user', $subRoles['managegame'])) { ?>
        <li class="nav-item"> <a class="nav-link" href="manage_user.php">Users</a></li>
      <?php } ?>
      <?php if (in_array('addgiftcode', $subRoles['managegame'])) { ?>
        <li class="nav-item"> <a class="nav-link" href="addgiftcode.php">Gift Code</a></li>
      <?php } ?>
      <ul class="nav flex-column sub-menu">
          <li class="nav-item"> <a class="nav-link" href="manage_redenvlopereq.php">Red Env. Request</a></li>
     </ul>
      <?php if (in_array('demouser', $subRoles['managegame'])) { ?>
        <li class="nav-item"> <a class="nav-link" href="demouser.php">Demo User</a></li>
      <?php } ?>
    </ul>
  </div>
</li>


          
          <?php
}
if ($setting == 1 && isset($subRoles['setting'])) {
?>
<li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#ui-basic-6" aria-expanded="false" aria-controls="ui-basic-6">
    <i class="material-icons menu-icon">settings</i>
    <span class="menu-title">Setting</span>
    <i class="menu-arrow"></i>
  </a>
  <div class="collapse" id="ui-basic-6">
    <ul class="nav flex-column sub-menu">
      <?php if (in_array('update_banner', $subRoles['setting'])) { ?>
        <li class="nav-item"><a class="nav-link" href="update_banner.php">Banner Update</a></li>
      <?php } ?>
       <?php if (in_array('update_banner', $subRoles['setting'])) { ?>
        <li class="nav-item"><a class="nav-link" href="update_category_banner.php">Category Banner Update</a></li>
        <?php } ?>
      <?php if (in_array('update_activitybanner', $subRoles['setting'])) { ?>
        <li class="nav-item"><a class="nav-link" href="update_activitybanner.php">Manage Activity Banner</a></li>
      <?php } ?>
      <?php if (in_array('manage_custom_activitybanner', $subRoles['setting'])) { ?>
        <li class="nav-item"><a class="nav-link" href="manage_custom_activitybanner.php">Activity Custom Banner</a></li>
      <?php } ?>
      
      <?php if (in_array('websetting', $subRoles['setting'])) { ?>
        <li class="nav-item"><a class="nav-link" href="websetting.php">Website Setting</a></li>
      <?php } ?>
      <?php if (in_array('update_commission', $subRoles['setting'])) { ?>
        <li class="nav-item"><a class="nav-link" href="update_commission.php">Commission Setting</a></li>
      <?php } ?>
      
      <?php if (in_array('update_invitebonus', $subRoles['setting'])) { ?>
        <li class="nav-item"><a class="nav-link" href="update_invitebonus.php">Invite Bonus Setting</a></li>
      <?php } ?>
      <?php if (in_array('mtg_bot_Predction', $subRoles['setting'])) { ?>
        <li class="nav-item"><a class="nav-link" href="mtg_bot_Predction.php">Manage Bot Prediction</a></li>
      <?php } ?>
      <?php if (in_array('manage_walletuser', $subRoles['setting'])) { ?>
        <li class="nav-item"><a class="nav-link" href="manage_walletuser.php">Manage User Wallet</a></li>
      <?php } ?>
      
      <?php if (in_array('manage_site_message', $subRoles['setting'])) { ?>
        <li class="nav-item"><a class="nav-link" href="manage_site_message.php">Site Welcome Message</a></li>
      <?php } ?>
      <?php if (in_array('manage_webmessage', $subRoles['setting'])) { ?>
        <li class="nav-item"><a class="nav-link" href="manage_webmessage.php">Site Web Message</a></li>
      <?php } ?>
     <?php if (in_array('manage_langcurrency', $subRoles['setting'])) { ?>
        <li class="nav-item"><a class="nav-link" href="manage_langcurrency">Web Currency&Lang </a></li>
      <?php } ?>
      
      <?php if (in_array('admin-css-editor', $subRoles['setting'])) { ?>
        <li class="nav-item"><a class="nav-link" href="admin-css-editor">Web Colors  </a></li>
      <?php } ?>
      
    </ul>
  </div>
</li>


          
          <?php
}
if ($marketing == 1 && isset($subRoles['marketing'])) {
?>
<li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#ui-basic-7" aria-expanded="false" aria-controls="ui-basic-7">
    <i class="material-icons menu-icon">campaign</i>
    <span class="menu-title">Marketing</span>
    <i class="menu-arrow"></i>
  </a>
  <div class="collapse" id="ui-basic-7">
    <ul class="nav flex-column sub-menu">
      <?php if (in_array('user_subtree', $subRoles['marketing'])) { ?>
        <li class="nav-item"><a class="nav-link" href="user_subtree.php">Users SubsTree</a></li>
      <?php } ?>
      <?php if (in_array('users_trunover', $subRoles['marketing'])) { ?>
        <li class="nav-item"><a class="nav-link" href="users_trunover.php">Users Turnover</a></li>
      <?php } ?>
      <?php if (in_array('manage_turntable', $subRoles['marketing'])) { ?>
        <li class="nav-item"><a class="nav-link" href="manage_turntable.php">TurnTable Manage</a></li>
      <?php } ?>
      <?php if (in_array('manage_dailysignin', $subRoles['marketing'])) { ?>
        <li class="nav-item"><a class="nav-link" href="manage_dailysignin.php">Daily Sign Bonus</a></li>
      <?php } ?>
      <?php if (in_array('osscontens', $subRoles['marketing'])) { ?>
        <li class="nav-item"><a class="nav-link" href="osscontens.php">Cloud Images Store</a></li>
      <?php } ?>
      <?php if (in_array('manage_rank', $subRoles['marketing'])) { ?>
        <li class="nav-item"><a class="nav-link" href="manage_rank.php">Users Rank</a></li>
      <?php } ?>
    </ul>
  </div>
</li>


                
             <?php
}
if ($agents == 1 && isset($subRoles['agents'])) {
?>
<li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#ui-basic-8" aria-expanded="false" aria-controls="ui-basic-8">
    <i class="material-icons menu-icon">supervisor_account</i>
    <span class="menu-title">Agents</span>
    <i class="menu-arrow"></i>
  </a>
  <div class="collapse" id="ui-basic-8">
    <ul class="nav flex-column sub-menu">
      <?php if (in_array('agentuser', $subRoles['agents'])) { ?>
        <li class="nav-item"><a class="nav-link" href="agentuser">Agent User</a></li>
      <?php } ?>
      <?php if (in_array('agents_management', $subRoles['agents'])) { ?>
        <li class="nav-item"><a class="nav-link" href="agents_management">Agent Summary</a></li>
      <?php } ?>
      <?php if (in_array('fetch_agent_recharge', $subRoles['agents'])) { ?>
        <li class="nav-item"><a class="nav-link" href="fetch_agent_recharge">Agents Recharges</a></li>
      <?php } ?>
    </ul>
  </div>
</li>


<?php
}
if ($teachers == 1 && isset($subRoles['teachers'])) {
?>
<li class="nav-item">
  <a class="nav-link" data-toggle="collapse" href="#ui-basic-10" aria-expanded="false" aria-controls="ui-basic-10">
    <i class="material-icons menu-icon">manage_accounts</i>
    <span class="menu-title">Teachers</span>
    <i class="menu-arrow"></i>
  </a>
  
  <div class="collapse" id="ui-basic-10">
    <ul class="nav flex-column sub-menu">
      <?php if (in_array('manage_teachers', $subRoles['teachers'])) { ?>
        <li class="nav-item"><a class="nav-link" href="manage_teachers">Teachers User</a></li>
      <?php } ?>
  
</li>
    </ul>
  </div>
</li>






                
		  <?php
			}
		  ?>
		 <li class="nav-item">
                    <a href="https://xentra1.site/" class="nav-link">
                        <i class="nav-icon fa fa-sign-out" aria-hidden="true"></i>
                        <p>Go To Website</p>
                    </a>
                </li>
        </ul>
        
        
        
        <!-- ✅ Place at the very bottom of compas.php -->
<script src="vendors/base/vendor.bundle.base.js"></script>
<script src="js/off-canvas.js"></script>
<script src="js/hoverable-collapse.js"></script>
<script src="js/template.js"></script>
<script src="vendors/chart.js/Chart.min.js"></script>
<script src="vendors/jquery-bar-rating/jquery.barrating.min.js"></script>
<script src="js/dashboard.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>

<script>
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }

  document.addEventListener("DOMContentLoaded", function () {
    const current = window.location.pathname.split("/").pop();
    document.querySelectorAll(".nav-link").forEach(link => {
      const href = link.getAttribute("href");
      if (href && current === href) {
        link.classList.add("active");

        const collapseDiv = link.closest(".collapse");
        if (collapseDiv) {
          collapseDiv.classList.add("show");
          const toggleLink = document.querySelector(`a[href="#${collapseDiv.id}"]`);
          if (toggleLink) toggleLink.setAttribute("aria-expanded", "true");
        }
      }
    });
  });
</script>

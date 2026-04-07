<?php
if (!function_exists('header_image_account')) {
    function header_image_account()
    {
?>
<table class="header-account" cellspacing="0" cellpadding="0" border="0" width="100%" style="--account-header-bg-image:url('<?php echo htmlspecialchars(spp_modern_header_image_url('account_bg.jpg'), ENT_QUOTES); ?>');">
  <tbody>
    <tr>
      <td class="header-bg">
        <img src="<?php echo htmlspecialchars(spp_modern_header_image_url('title_acc_man.gif'), ENT_QUOTES); ?>" alt="Account Management" class="account-header-title">
      </td>
    </tr>
    <tr>
      <td class="header-bottom">
        <img src="<?php echo htmlspecialchars(spp_modern_header_image_url('bottom.gif'), ENT_QUOTES); ?>" alt="Bottom border" class="header-bottomimg">
      </td>
    </tr>
  </tbody>
</table>
<?php
    }
}

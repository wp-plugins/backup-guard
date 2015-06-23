<?php
if(@$includeCss)
{
    foreach ($includeCss as $css)
    {
        echo '<link rel="stylesheet" type="text/css" href="' . SG_PUBLIC_URL . 'css/' . $css . '.css' . '">';
    }
}
?>
<div class="sg-spinner"></div>
<div class="sg-wrapper-less" style="width: 80%;">
    <div id="sg-wrapper" style="display: none;">
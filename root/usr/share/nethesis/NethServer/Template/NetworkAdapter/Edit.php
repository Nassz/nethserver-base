<?php
/* @var $view \Nethgui\Renderer\Xhtml */

echo $view->header('deviceInfos')->setAttribute('template', $T('Edit_header'));
echo $view->textLabel('deviceInfos')->setAttribute('template', $T('Edit_description'))->setAttribute('tag', 'div')->setAttribute('class', 'labeled-control wspreline');
echo $view->selector('role', $view::SELECTOR_DROPDOWN);
echo $view->radioButton('bootproto', 'dhcp');
echo $view->fieldsetSwitch('bootproto', 'none', $view::FIELDSETSWITCH_EXPANDABLE)
    ->insert($view->textInput('ipaddr'))
    ->insert($view->textInput('netmask'))
    ->insert($view->textInput('gateway'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_CANCEL | $view::BUTTON_HELP);

$view->includeCSS("
  tr td:first-child  {
      font-weight: bold;
      color: #333;
  }
  tr.red td:first-child  {
       color: red;
  }
  tr.blue td:first-child  {
       color: blue;
  }
  tr.orange td:first-child  {
       color: orange;
  }
  tr.green td:first-child  {
       color: green;
  }
  tr.grey td:first-child  {
       color: grey;
  }
");

$roleId = $view->getClientEventTarget('role');
$bootprotoId = $view->getClientEventTarget('bootproto');
$view->includeJavascript("
(function ( $ ) {
    function toggleDHCP() {
       if ($('.${roleId}').val().indexOf('red') !== -1) {
           $('.${bootprotoId}[value=dhcp]').prop('disabled', false);
       } else {
            // role is not red
           $('.${bootprotoId}[value=static]').trigger('click');
           $('.${bootprotoId}[value=dhcp]').prop('disabled', true);
       }
    }
    $(document).ready(function() {
       toggleDHCP();
       $('.${roleId}').change(toggleDHCP);
       $('.${roleId}').on('nethguiupdateview', toggleDHCP);
    });
} ( jQuery ));
");
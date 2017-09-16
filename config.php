<?php

require_once(INCLUDE_DIR.'/class.plugin.php');
require_once(INCLUDE_DIR.'/class.forms.php');


class RadiobuttonsConfig extends PluginConfig {

    // Provide compatibility function for versions of osTicket prior to
    // translation support (v1.9.4)
    function translate() {
        if (!method_exists('Plugin', 'translate')) {
            return array(
                function($x) { return $x; },
                function($x, $y, $n) { return $n != 1 ? $y : $x; },
            );
        }
        return Plugin::translate('field-radiobuttons');
    }

    function getOptions() {
        list($__, $_N) = self::translate();
        return array(
            'category' => new TextboxField(array(
                'label' => $__('Choose category'),
                'hint' => $__('What category do you want the field to appear under.'),
                'default' => $__('Basic Fields'),
                'configuration' => array('size'=>40, 'length'=>60),
            )),
            'uninstall-method' => new ChoiceField([
                'label' => $__('Uninstall method'),
                'required' => false,
                'hint' => $__('Select what you want the plugin to do when uninstalled.'),
                'default' => 'convert',
                'choices' => array(
                    'convert' => __('Convert radiobuttons to choices'),
                    'prevent' => __('If radiobuttons remain prevent uninstall'),
                    'warn' => __('Inform the admin if instances remain'),
                    'nothing' => __('Just remove the plugin'),
                )
            ])
        );
    }
}

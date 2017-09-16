<?php
require_once(INCLUDE_DIR.'class.plugin.php');
require_once('config.php');

class RadioField extends FormField {
    static $widget = 'RadioWidget';
    var $_choices;

    function getConfigurationOptions() {
        return array(
            'choices'  =>  new TextareaField(array(
                'id'=>1, 'label'=>__('Choices'), 'required'=>true, 'default'=>'',
                'hint'=>__('List choices, one per line. To protect against spelling changes, specify key:value names to preserve entries if the list item names change'),
                'configuration'=>array('html'=>false)
            )),
            'default' => new TextboxField(array(
                'id'=>3, 'label'=>__('Default'), 'required'=>false, 'default'=>'',
                'hint'=>__('(Enter a key). Value selected from the list initially'),
                'configuration'=>array('size'=>20, 'length'=>40),
            )),
        );
    }

    function parse($value) {
        return $this->to_php($value ?: null);
    }

    function to_database($value) {
        if (!is_array($value)) {
            $choices = $this->getChoices();
            if (isset($choices[$value]))
                $value = array($value => $choices[$value]);
        }
        if (is_array($value))
            $value = JsonDataEncoder::encode($value);

        return $value;
    }

    function to_php($value) {
        if (is_string($value))
            $value = JsonDataParser::parse($value) ?: $value;

        // CDATA table may be built with comma-separated key,value,key,value
        if (is_string($value) && strpos($value, ',')) {
            $values = array();
            $choices = $this->getChoices();
            $vals = explode(',', $value);
            foreach ($vals as $V) {
                if (isset($choices[$V]))
                    $values[$V] = $choices[$V];
            }
            if (array_filter($values))
                $value = $values;
            elseif($vals)
                list($value) = $vals;

        }
        $config = $this->getConfiguration();
        if (is_array($value) && count($value) < 2) {
            reset($value);
            $value = key($value);
        }
        return $value;
    }

    function toString($value) {
        if (!is_array($value))
            $value = $this->getChoice($value);
        if (is_array($value))
            return implode(', ', $value);
        return (string) $value;
    }

    function getKeys($value) {
        if (!is_array($value))
            $value = $this->getChoice($value);
        if (is_array($value))
            return implode(', ', array_keys($value));
        return (string) $value;
    }

    function whatChanged($before, $after) {
        $B = (array) $before;
        $A = (array) $after;
        $added = array_diff($A, $B);
        $deleted = array_diff($B, $A);
        $added = array_map(array($this, 'display'), $added);
        $deleted = array_map(array($this, 'display'), $deleted);

        if ($added && $deleted) {
            $desc = sprintf(
                __('added <strong>%1$s</strong> and removed <strong>%2$s</strong>'),
                implode(', ', $added), implode(', ', $deleted));
        }
        elseif ($added) {
            $desc = sprintf(
                __('added <strong>%1$s</strong>'),
                implode(', ', $added));
        }
        elseif ($deleted) {
            $desc = sprintf(
                __('removed <strong>%1$s</strong>'),
                implode(', ', $deleted));
        }
        else {
            $desc = sprintf(
                __('changed from <strong>%1$s</strong> to <strong>%2$s</strong>'),
                $this->display($before), $this->display($after));
        }
        return $desc;
    }

    function getCriteria() {
        $config = $this->getConfiguration();
        $criteria = array();
        if (isset($config['criteria']))
            $criteria = $config['criteria'];

        return $criteria;
    }

    function getChoice($value) {

        $choices = $this->getChoices();
        $selection = array();
        if ($value && is_array($value)) {
            $selection = $value;
        } elseif (isset($choices[$value]))
            $selection[] = $choices[$value];
        elseif ($this->get('default'))
            $selection[] = $choices[$this->get('default')];

        return $selection;
    }

    function getChoices($verbose=false) {
        if ($this->_choices === null || $verbose) {
            // Allow choices to be set in this->ht (for configurationOptions)
            $this->_choices = $this->get('choices');
            if (!$this->_choices) {
                $this->_choices = array();
                $config = $this->getConfiguration();
                $choices = explode("\n", $config['choices']);
                foreach ($choices as $choice) {
                    // Allow choices to be key: value
                    list($key, $val) = explode(':', $choice);
                    if ($val == null)
                        $val = $key;
                    $this->_choices[trim($key)] = trim($val);
                }
                // Add old selections if nolonger available
                // This is necessary so choices made previously can be
                // retained
                $values = ($a=$this->getAnswer()) ? $a->getValue() : array();
                if ($values && is_array($values)) {
                    foreach ($values as $k => $v) {
                        if (!isset($this->_choices[$k])) {
                            if ($verbose) $v .= ' (retired)';
                            $this->_choices[$k] = $v;
                        }
                    }
                }
            }
        }
        return $this->_choices;
    }

    function lookupChoice($value) {
        return null;
    }

    function getSearchMethods() {
        return array(
            'set' =>        __('has a value'),
            'nset' =>     __('does not have a value'),
            'includes' =>   __('includes'),
            '!includes' =>  __('does not include'),
        );
    }

    function getSearchMethodWidgets() {
        return array(
            'set' => null,
            'nset' => null,
            'includes' => array('RadioField', array(
                'choices' => $this->getChoices(),
            )),
            '!includes' => array('RadioField', array(
                'choices' => $this->getChoices(),
            )),
        );
    }

    function getSearchQ($method, $value, $name=false) {
        $name = $name ?: $this->get('name');
        switch ($method) {
        case '!includes':
            return Q::not(array("{$name}__in" => array_keys($value)));
        case 'includes':
            return new Q(array("{$name}__in" => array_keys($value)));
        default:
            return parent::getSearchQ($method, $value, $name);
        }
    }

    function describeSearchMethod($method) {
        switch ($method) {
        case 'includes':
            return __('%s includes %s');
        case 'includes':
            return __('%s does not include %s' );
        default:
            return parent::describeSearchMethod($method);
        }
    }
}

class RadioWidget extends Widget {
    
    function render($options=array()) {

        $config = $this->field->getConfiguration();

        // Determine the value for the default (the one listed if nothing is
        // selected)
        $choices = $this->field->getChoices(true);

        $have_def = false;
        // We don't consider the 'default' when rendering in 'search' mode
        $def_key = $this->field->get('default');
        if (!$def_key && $config['default'])
            $def_key = $config['default'];
        if (is_array($def_key))
            $def_key = key($def_key);
        $have_def = isset($choices[$def_key]);
        $def_val = $have_def ? $choices[$def_key] : '';

        $values = $this->value;
        if (!is_array($values) && isset($values)) {
            $values = array($values => $this->field->getChoice($values));
        }

        if (!is_array($values))
            $values = $have_def ? array($def_key => $choices[$def_key]) : array();

        if (isset($config['classes']))
            $classes = 'class="'.$config['classes'].'"';
        
        $this->emitChoices($choices, $values, $have_def, $def_key);
    }

    function emitChoices($choices, $values=array(), $have_def=false, $def_key=null) {
        reset($choices);

        foreach ($choices as $key => $name) {
            if (!$have_def && $key == $def_key)
                continue; ?>
            <input type="radio" name="<?php echo $this->name; ?>[]" value="<?php echo $key; ?>" <?php
                if (isset($values[$key])) echo 'checked="checked"';
            ?>> <?php echo Format::htmlchars($name); ?>
        <?php
        }
    }

    function getValue() {

        if (!($value = parent::getValue()))
            return null;

        if ($value && !is_array($value))
            $value = array($value);

        // Assume multiselect
        $values = array();
        $choices = $this->field->getChoices();

        if ($choices && is_array($value)) {
            // Complex choices
            if (is_array(current($choices))
                    || current($choices) instanceof Traversable) {
                foreach ($choices as $label => $group) {
                     foreach ($group as $k => $v)
                        if (in_array($k, $value))
                            $values[$k] = $v;
                }
            } else {
                foreach($value as $k => $v) {
                    if (isset($choices[$v]))
                        $values[$v] = $choices[$v];
                    elseif (($i=$this->field->lookupChoice($v)))
                        $values += $i;
                }
            }
        }

        return $values;
    }

    function getJsValueGetter() {
        return '%s.is(":checked").val()';
    }
}

class RadiobuttonsPlugin extends Plugin {
    var $config_class = 'RadiobuttonsConfig';

    function bootstrap() {
        $config = $this->getConfig();
        FormField::$types[$config->get('category')]['radiobutton'] = array(   /* @trans */ 'Radiobuttons', 'RadioField');
    }
    
    function uninstall() {
        global $ost;
        $errors = array();
        $config = $this->getConfig();
        
        $fields = DynamicFormField::objects()->filter(array('type' => 'radiobutton'))->all();
        if(count($fields) > 0){
            switch($config->get('uninstall-method')){
                case 'prevent':
                    $ost->setWarning(sprintf(__('%d instance(s) of radiobuttons remaining.'), count($fields)));
                    return false;
                    break;
                case 'warn':
                    $ost->alertAdmin(__('Error! Plugin Radiobuttons Field has been uninstalled but is in use!'),
                        __('This field type has been added to a Form, you will have errors!'),
                        true);
                    break;
                case 'convert':
                    for($i = 0; $i < count($fields); $i++){
                        $fields[$i]->set('type', 'choices');
                        $fields[$i]->save();
                    }
                    break;
            }
        }
        return parent::uninstall($errors);
    }
}

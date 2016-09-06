<?php
/**
 * Created by http://eddmash.com
 * User: eddmash
 * Date: 7/16/16
 * Time: 2:12 PM.
 */
namespace powerorm\form\widgets;

/**
 * Hidden input: <input type='hidden' ...>.
 *
 * Note that there also is a MultipleHiddenInput widget that encapsulates a set of hidden input elements.
 *
 * @since 1.1.0
 *
 * @author Eddilbert Macharia (http://eddmash.com) <edd.cowan@gmail.com>
 */
class HiddenInput extends TextInput
{
    public $input_type = 'hidden';
}

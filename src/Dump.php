<?php

class Dump
{
    private $name      = '#f07b06';//default orrange
    private $value     = '#0000ff';//default blue (for string value)
    private $data_n    = '#bbbbbb';//default gray (for 'string')
    private $data_ty   = '#5ba415';//default lemon (for lenght or size)
    private $n_array   = '#000000';//default black (for array or objects)
    private $bool      = '#bb02ff';//default light purple (for bool)
    private $d_null    = '#6789f8';//default light blue (for null)
    private $float     = '#9c6e25';//default brown (for float)
    private $pnt       = '#f00000';//default red (for refrences like '=>' and ':')
    private $npnt      = '#e103c4';//default pink (for '=')
    private $inte      = '#1baabb';//default greenishblue (for int)
    private $a_pt      = '#59829e';//default light navy blue (for array key)
    private $visib     = '#741515';//default dark red (for object visibility)
    private $var_n     = '#987a00';//default light brown (for object variable $name )
    private $stat      = '#3465A4';//default dark navy blue (for static property $name )

    private $marg = 20;
    private $arr_count = null;
    private $detem_last = 1;
    private $proc_end = false;
    private $instance = true;

    private static $configuration = array();

    public function __construct()
    {
        if ( ! empty(self::$configuration))
		{
            foreach (self::$configuration as $name => $value)
			{
                if (property_exists($this, $name))
				{
                    $this->{$name} = $value;
                }
            }
        }
        echo $this->logic(func_get_args());
    }

    /*
    * updates property value
    */
    public static function config($name, $new_value)
    {
        self::$configuration[$name] = $new_value;
    }

    /*
    * gets object property
    */
    private function objects($object, &$indent)
    {
        $vals = array();
        $obj = new \ReflectionObject($object);

        $vals['class'] = $obj->getName();
        foreach ($obj->getProperties() as $key =>  $prop)
		{
            //the &nbsp; is to make sure visibilities are aligned
            if ($prop->isPrivate())
			{
                $type = 'private ~~ &nbsp;&nbsp;&nbsp;';
                if ($prop->isStatic())
				{
                    $type = 'private <i style="color:' . $this->stat . ';">static</i>&nbsp;&nbsp;&nbsp;';
                    $indent = true;
                }
            }
            elseif ($prop->isProtected())
			{
                $type = 'protected ~~ &nbsp;';
                if ($prop->isStatic())
				{
                    $type = 'protected <i style="color:' . $this->stat . ';">static</i>&nbsp;';
                    $indent = true;
                }

            }
            elseif ($prop->isPublic())
			{
                $type = 'public ~~ &nbsp;&nbsp;&nbsp;&nbsp;';
                if ($prop->isStatic())
				{
                    $type = 'public <i style="color:' . $this->stat . ';">static</i>&nbsp;&nbsp;&nbsp;&nbsp;';
                    $indent = true;
                }
            }
            $vals[$key]['visibility'] = $type;
            $prop->setAccessible(true);
            $vals[$key]['name'] = $prop->getName();
            $vals[$key]['value'] = $prop->getValue($object);
        }
        return $vals;
    }

    private function logic()
    {
        $args = func_get_args();
        if ($this->instance)
		{
            $args = $args[0];
            $this->instance = false;
        }

        $dumped = '';
        for ($i = 0; $i < count($args); $i++)
        {
            $data_type = gettype($args[$i]);
            if ($data_type == 'string')
			{
                $length = strlen($args[$i]);
                $dumped .= '<code><span style="color:' . $this->value . ';">\'' . htmlspecialchars($args[$i]);
                $dumped .= '\'</span> <i style="color:' .$this->data_ty . ';">(length=' . $length . ')</i>';
                $dumped .= '<small style="color:' . $this->data_n . ';"> string</small></code><br />';
            }
            elseif ($data_type == 'integer')
			{
                $dumped .= '<code><span style="color:' . $this->inte . ';">' .  htmlspecialchars($args[$i]) . '</span>';
                $dumped .= ' <small style="color:' . $this->data_n . ';"> int</small></code><br />';
            }
            elseif ($data_type == 'double')
			{
                $dumped .= '<code><span style="color:' . $this->float . ';">' .  htmlspecialchars($args[$i]) . '</span>';
                $dumped .= '<small style="color:' . $this->data_n . ';"> float</small></code><br />';
            }
            elseif ($data_type == 'boolean')
			{
                $dumped .= '<code><span style="color:' . $this->bool . ';">';
                $dumped .= ($args[$i])? 'true</span>':'false</span>';
                $dumped .= '<small style="color:' . $this->data_n . ';"> boolean</small></code><br />';
            }
            elseif ($data_type == 'NULL')
			{
                $dumped .= '<code><span style="color:' . $this->d_null . ';">null</span></code><br />';
            }
            elseif ($data_type == 'array')
			{
                $length = count($args[$i]);
                if (!$this->arr_count)
				{
                    $this->arr_count = count($args[$i], COUNT_RECURSIVE);
                }
                if (!$this->proc_end && $this->marg == 20)
				{
                    $dumped .= '<code><b style="color:' . $this->n_array . ';">array</b> ';
                    $dumped .= '<i style="color:' .$this->data_ty . ';">(size=' . $length . ')</i> [<br />';
                    if ($length == 0)
					{
                        $this->marg += 20;
                        $dumped .= '<code style="margin-left:' .$this->marg. 'px;">(empty)</code>';
                        $this->marg -= 20;
                        $dumped .= '<br /><code style="margin-left:' .$this->marg. 'px;">]</code> <br />';
                    }
                }
                foreach ($args[$i] as $key => $values)
				{
                    if (is_array($values))
					{
                        $this->marg += 20;
                        $length = count($values);
                        $dumped .= '<code style="margin-left:' .$this->marg. 'px;">';
                        $dumped .= '<span style="color:'. $this->a_pt . '">\'' . $key . '\'</span>';
                        $dumped .=  '</span> <span style="color:'. $this->npnt . '">=</span> ';
                        $dumped .= ' <b style="color:'. $this->n_array .';">array</b>';
                        $dumped .= ' <i style="color:' .$this->data_ty . ';">(size = ' . $length . ')';
                        $dumped .= '</i> { </code><br />' . $this->logic($values);
                        $dumped .= '<code style="margin-left:' .$this->marg. 'px;">}</code> <br />';
                        $this->marg -= 20;
                    }
                    else
					{
                        $this->marg += 20;
                        $dumped .= '<code style="margin-left:' .$this->marg. 'px;">';
                        $dumped .= '<span style="color:'. $this->name . '">\'' . $key;
                        $dumped .= '\'</span> </span> <span style="color:'. $this->pnt . '">=>';
                        $dumped .= '</span> </code>' . $this->logic($values);
                        $this->marg -= 20;
                    }
                    if ($this->marg == 20 && $this->arr_count == $this->detem_last)
					{
                        $dumped .= '<code style="margin-left:' .$this->marg. 'px;">]<br /></code>';
                        $this->proc_end = false;
                        $this->arr_count = null;
                        $this->detem_last = 1;
                    }
                    else
					{
                        $this->proc_end = true;
                        $this->detem_last++;
                    }
                }
            }
            elseif ($data_type == 'object')
			{
                $indent = false;
                $object = $this->objects($args[$i], $indent);

                $dumped .= '<code><b style="color:' . $this->n_array . ';">';
                $dumped .= 'object</b> <i style="color:' .$this->data_ty . ';">';
                $dumped .= '(' . $object['class'] . ')</i><br />';
                $this->marg += 20;
                foreach ($object as $key => $values)
				{

                    //match object property indentation
                    if ($indent && isset($values['visibility']))
					{
                        $values['visibility'] = str_replace(
                            '~~',
                            str_repeat('&emsp;', 3),
                            $values['visibility']
                        );
                    }
					else
					{
                        if (isset($values['visibility']))
						{
							$values['visibility'] = str_replace('~~','&nbsp;', $values['visibility']);
                        }
                    }

                    if (is_array($values))
					{
                        $dumped .= '<code style="margin-left:' .$this->marg. 'px;">';
                        $dumped .= '<span style="color:'. $this->visib . '">' . $values['visibility'] . '</span>';
                        $dumped .= '</span> <span style="color:'. $this->var_n . '">';

                        if (is_array($values['value']))
						{
                            $current_marg = $this->marg + 30;
                            $length= count($values['value']);
                            $dumped .= '\'' . $values['name'] . '\' </span>';
                            $dumped .= '<span style="color:'. $this->pnt . '"> : </span><br />';
                            $dumped .= '<code style="margin-left:' .$current_marg. 'px;">';
                            $dumped .= '<b style="color:' . $this->n_array . ';">array</b> ';
                            $dumped .= '<i style="color:' .$this->data_ty . ';">(size=' . $length . ')</i> [<br />';

                            $this->marg += 30;
                            if ($length == 0)
							{
                                $merg = $current_marg+20;
                                $dumped .= '<code style="margin-left:' .$merg. 'px;">(empty)</code><br />';
                            }
							else
							{
                                $dumped .= $this->logic($values['value']);
                            }
                            $this->marg -= 30;
                            $dumped .= '</code><code style="margin-left:' .$current_marg. 'px;">';
                            $dumped .= ']<br />';
                        }
                        else
						{
                            $dumped .= '\'' . $values['name'] . '\' </span>';
                            $dumped .= '<span style="color:'. $this->pnt . '"> : </span>';
                            $dumped .= $this->logic($values['value']);
                        }

                    }
                }
                $dumped .= '<br />';
                $this->marg -= 20;
            }
        }
         return $dumped;
    }


}
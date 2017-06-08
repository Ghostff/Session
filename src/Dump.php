<?php

/**
 * Bittr
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

class Dump
{
    /**
     * @var string - null data type
     */
    private $_null = '6789f8';
    /**
     * @var string - variable type
     */
    private $_type = 'AAAAAA';
    /**
     * @var string - bool data type
     */
    private $_bool = 'bb02ff';
    /**
     * @var string - array data type
     */
    private $_array	= '000000';
    /**
     * @var string - float data type
     */
    private $_float = '9C6E25';
    /**
     * @var string - double data type
     */
    private $_double = '9C6E25';
    /**
     * @var string - string data  type
     */
    private $_string = '0000FF';
    /**
     * @var string - length of any data value
     */
    private $_lenght = '5BA415';
    /**
     * @var string - int data type
     */
    private $_integer = '1BAABB';
    /**
     * @var string - object data type
     */
    private $_object = '000000';
    /**
     * @var string - object properties visibility
     */
    private $_vsble = '741515';
    /**
     * @var string - object name
     */
    private $_object_name = '5ba415';
    /**
     * @var string - object property name
     */
    private $_obj_prop_name = '987a00';
    /**
     * @var string - object property name and value separator
     */
    private $_obj_prop_acc = 'f00000';
    /**
     * @var string - array of array key
     */
    private $_parent_arr = '59829e';
    /**
     * @var string - array of array accessor symbol
     */
    private $_parent_arr_acc = 'e103c4';
    /**
     * @var string - array
     */
    private $_child_arr = 'f07b06';
    /**
     * @var string - array value accessor symbol
     */
    private $_child_arr_acc = 'f00000';

    /**
     * @var array - runtime color buffer
     */
    private static $configurations = [];


    /**
     * Dump constructor.
     */
    public function __construct()
    {
        if (self::$configurations !== [])
        {
            foreach (self::$configurations as $name => $value)
            {
                $_name = '_' . $name;
                if (property_exists($this, $_name))
                {
                    $this->{$_name} = $value;
                }
                else
                {
                    throw new RuntimeException('property ' . $name . ' does not exist');
                }
            }
        }

        $bt = debug_backtrace();
        $file = $bt[0]['file'] . '(line:' . $bt[0]['line'] . ')';
        $file = '<span class="type" style="font-size:10px;color:7px;">' . $file . '</span><br />';
        echo  '<code>' . $file . $this->format(func_get_args()) . '</code>';
    }


    /**
     * updates color properties value
     *
     * @param string $name
     * @param string $new_value
     */
    public static function set(string $name, string $new_value): void
    {
        self::$configurations[$name] = $new_value;
    }

    /**
     * object argument format
     *
     * @param $objects
     * @return string
     */
    private function objects($objects): string
    {
        $obj = new \ReflectionObject($objects);

        $temp = '<span class="object" style="font-weight:bold;color:#' . $this->_object . '">object -></span>';
        $format = '<div style="padding-left:20px;" class="obj_prop">';
        $size = 0;

        foreach ($obj->getProperties() as $size => $prop)
        {
            if ($prop->isPrivate())
            {
                $format .= '<span class="private" style="color:#' . $this->_vsble . '">private&nbsp;&nbsp; </span>';
            }
            elseif ($prop->isProtected())
            {
                $format .= '<span class="protected" style="color:#' . $this->_vsble . '">protected </span>';
            }
            elseif ($prop->isPublic())
            {
                $format .= '<span class="public" style="color:#' . $this->_vsble . '">public&nbsp;&nbsp;&nbsp; </span>';
            }

            $format .= '<span class="_obj_prop_name" style="color:#' . $this->_obj_prop_name . '">' . $prop->getName() . '</span>';
            $format .= '<span class="obj_prop_accessor" style="color:#' . $this->_obj_prop_acc . '"> : </span>';

            $prop->setAccessible(true);
            $format .= $this->format([$prop->getValue($objects)]);
            $size++;
        }

        $name =  '(' . $obj->getName() . ')';
        $temp .= '<span class="object" style="font-style:italic;color:#' . $this->_object_name . '">' . $name . '</span>';
        $temp .= '<span class="lenght" style="color:#' . $this->_lenght . '">';
        $temp .= '(size=' . ($size) . ')</span>';

        $temp .= $format . '</div>';
        return $temp;
    }

    /**
     * formats argument
     *
     * @param array $arguments
     * @param bool $array_loop
     * @return string
     */
    private function format(array $arguments, bool $array_loop = false): string
    {
        $format = '';
        foreach ($arguments as $arg)
        {
            $type = gettype($arg);
            if ($type == 'string')
            {
                #prevent html string output. we don't necessary need to replace the closing tag. str_replace(['<', '>'], ['&lt;', '&gt;'], $arg)
                #And using htmlspecialchars or htmlentities is won't be a good idea,
                #since some data might end up being striped during HTML entities conversion eg: pseudo-random bytes.
                $arg =  str_replace('<', '&lt;', $arg);
                $format .= '<span class="string" style="color:#' . $this->_string . '">\'' . $arg . '\'</span>';
                $format .= '<span class="lenght" style="color:#' . $this->_lenght . '">';
                $format .= '(length=' . strlen($arg) . ')</span>';
                $format .= '<span class="type" style="font-size:10px;margin-left:7px;color:#' . $this->_type . '">';
                $format .= $type . '</span>';
            }
            elseif ($type == 'integer')
            {
                $format .= '<span class="integer" style="color:#' . $this->_integer . '">' . $arg . '</span>';
                $format .= '<span class="type" style="font-size:10px;margin-left:7px;color:#' . $this->_type . '">';
                $format .= $type . '</span>';
            }
            elseif ($type == 'boolean')
            {
                $arg = ($arg) ? 'true' : 'false';
                $format .= '<span class="bool" style="color:#' . $this->_bool . '">' . $arg . '</span>';
                $format .= '<span class="type" style="font-size:10px;margin-left:7px;color:#' . $this->_type . '">';
                $format .= $type . '</span>';
            }
            elseif ($type == 'double')
            {
                $format .= '<span class="double" style="color:#' . $this->_double . '">' . $arg . '</span>';
                $format .= '<span class="type" style="font-size:10px;margin-left:7px;color:#' . $this->_type . '">';
                $format .= $type . '</span>';
            }
            elseif ($type == 'NULL')
            {
                $format .= '<span class="null" style="color:#' . $this->_null . '">null</span>';
                $format .= '<span class="type" style="font-size:10px;margin-left:7px;color:#' . $this->_type . '">';
                $format .= $type . '</span>';
            }
            elseif ($type == 'float')
            {
                $format .= '<span class="float" style="color:#' . $this->_float . '">' . $arg . '</span>';
                $format .= '<span class="type" style="font-size:10px;margin-left:7px;color:#' . $this->_type . '">';
                $format .= $type . '</span>';
            }
            elseif ($type == 'array')
            {
                if ( ! $array_loop)
                {
                    $format .= '<span class="string" style="font-weight:bold;color:#' . $this->_array . '">array</span>';
                    $format .= '<span class="lenght" style="margin:0 5px;color:#' . $this->_lenght . '">';
                    $format .= '(length=' . count($arg) . ')</span>';
                    $format .= '<span class="string" style="font-weight:bold;color:#' . $this->_array . '">[</span>';
                    $format .= '<div class="arr_content" style="padding-left:20px;">';
                }

                foreach ($arg as $key => $value)
                {
                    $key = str_replace('<', '&lt;', $key);
                    if ( is_array($value))
                    {
                        $format .= '<span class="string" style="color:#' . $this->_parent_arr . '">\'' . $key . '\'</span>';
                        $format .= '<span class="string" style="color:#' . $this->_parent_arr_acc . '"> = </span>';

                        $format .= '<span class="string" style="font-weight:bold;color:#' . $this->_array . '">array</span>';
                        $format .= '<span class="lenght" style="margin:0 5px;color:#' . $this->_lenght . '">';
                        $format .= '(length=' . count($value) . ')</span>';
                        $format .= '<span class="string" style="color:#' . $this->_array . '">{</span>';
                        $format .= '<div class="arr_content" style="padding-left:20px;">';

                        $format .= $this->format([$value], true);

                        $format .= '</div>';
                        $format .= '<span class="string" style="color:#' . $this->_array . '">}</span><br />';
                    }
                    else
                    {
                        $format .= '<span class="string" style="color:#' . $this->_child_arr . '">\'' . $key . '\'</span>';
                        $format .= '<span class="string" style="color:#' . $this->_child_arr_acc . '"> => </span>';
                        $format .= $this->format([$value], true);
                        $format .= '<br />';
                    }
                }

                if ( ! $array_loop)
                {
                    $format .= '</div>';
                    $format .= '<span class="string" style="font-weight:bold;color:#' . $this->_array . '">]</span>';
                }
            }
            elseif ($type == 'object')
            {
                $format .= $this->objects($arg);
            }

            if ( ! $array_loop)
            {
                $format .= '<br />';
            }
        }
        return str_replace('<br /></div><br />', '<br /></div>', $format);
    }
}
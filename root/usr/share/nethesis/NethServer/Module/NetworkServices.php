<?php
namespace NethServer\Module;

/*
 * Copyright (C) 2011 Nethesis S.r.l.
 * 
 * This script is part of NethServer.
 * 
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see <http://www.gnu.org/licenses/>.
 */

use Nethgui\System\PlatformInterface as Validate;

/**
 * Mange system network services
 * @author Giacomo Sanchietti
 *
 */
class NetworkServices extends \Nethgui\Controller\TableController
{

    protected function initializeAttributes(\Nethgui\Module\ModuleAttributesInterface $attributes)
    {
        return new \NethServer\Tool\CustomModuleAttributesProvider($attributes, array(
            'languageCatalog' => array('NethServer_Module_NetworkServices', 'NethServer_Module_Dashboard_Services'),
            'category' => 'Security')
        );
    }

    public function initialize()
    {
        $columns = array(
            'Key',
            'ports',
            'access',
            'Actions'
        );

        $this
            ->setTableAdapter($this->getPlatform()->getTableAdapter('configuration','service', function($key, $record) {
                if (!isset($record['TCPPorts']) && !isset($record['UDPPorts']) && !isset($record['TCPPort']) && !isset($record['UDPPort']) ) {
                    return false;
                }
                if ( !isset($record['access']) || !isset($record['status']) )  {
                    return false;
                }
                return true;
            }))
            ->addRowAction(new \NethServer\Module\NetworkServices\Modify('update'))
            ->addTableAction(new \Nethgui\Controller\Table\Help('Help'))
            ->setColumns($columns)
        ;

        parent::initialize();
    }

    public function prepareViewForColumnKey(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $d = $view->translate($key."_Description");
        if ($d && $d != $key."_Description") {
                $key .= " ($d)";
            return $key;
        }
        return $key;
    }

    public function prepareViewForColumnAccess(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $nets = '';
        if ( (isset($values['AllowHosts']) && $values['AllowHosts'])
            || (isset($values['DenyHosts']) && $values['DenyHosts']) ) {
            return '<span class="ns-black">custom</span>';
        }
        if ($values['access'] == 'private') {
            $nets = '<span class="ns-green">green</span>';
        } else if ($values['access'] == 'public') {
            $nets = '<span class="ns-green">green</span> <span class="ns-red">red</span>';
        } else {
            $nets = '<span class="ns-grey">localhost</span>';
        }

        return $nets;
    }

    /**
     *
     * @param \Nethgui\Controller\Table\Read $action
     * @param \Nethgui\View\ViewInterface $view
     * @param string $key The data row key
     * @param array $values The data row values
     * @return string|\Nethgui\View\ViewInterface
     */
    public function prepareViewForColumnPorts(\Nethgui\Controller\Table\Read $action, \Nethgui\View\ViewInterface $view, $key, $values, &$rowMetadata)
    {
        $ret = " ";
        $tcp = isset($values['TCPPort'])?$values['TCPPort']:"";
        if ($tcp == "" &&  isset($values['TCPPorts'])) {
             $tcp = $values['TCPPorts'];
        }
        $udp = isset($values['UDPPort'])?$values['UDPPort']:"";
        if ($udp == "" &&  isset($values['UDPPorts'])) {
             $udp = $values['UDPPorts'];
        }
        if ($tcp !== "") {
            $ret .= $view->translate("TCPPorts_label").": $tcp ";
        }
        if ($udp !== "") {
            $ret .= " ".$view->translate("UDPPorts_label").": $udp ";
        }
       
        return $ret;
    }

}

<?php

namespace NethServer\Module\NetworkAdapter;

/*
 * Copyright (C) 2014  Nethesis S.r.l.
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

/**
 * TODO: add component description here
 *
 * @author Davide Principi <davide.principi@nethesis.it>
 * @since 1.0
 */
class ConfirmInterfaceCreation extends \Nethgui\Controller\Table\AbstractAction
{

    public function initialize()
    {
        parent::initialize();
        $key = get_class($this->getParent());
        foreach (array('role', 'parts', 'type', 'dhcp', 'bootproto', 'gateway', 'ipaddr', 'netmask', 'bridge', 'vlan', 'vlanTag', 'bond') as $p) {
            $this->declareParameter($p, FALSE, array('SESSION', $key, $p));
        }
        $this->declareParameter('device', FALSE, array($this, 'getNewDeviceName'));
    }

    private function countType($type)
    {
        $counter = 0;
        foreach ($this->getAdapter() as $key => $props) {
            if ($props['type'] == $type) {
                $counter += 1;
            }
        }
        return $counter;
    }

    public function getNewDeviceName()
    {
        if ($this->parameters['type'] === 'bridge') {
            return sprintf('br%d', $this->countType('bridge'));
        } elseif ($this->parameters['type'] === 'bond') {
            return sprintf('bond%d', $this->countType('bond'));
        } elseif ($this->parameters['type'] === 'vlan') {
            return sprintf('%s.%s', $this->parameters['vlan'], $this->parameters['vlanTag']);
        }
        return NULL;
    }

    private function getActionsText(\Nethgui\View\ViewInterface $view)
    {
        $data = $this->parameters->getArrayCopy();
        if ($data['type'] == 'bridge') {
            $data['parts'] = str_replace(',', ', ', $data['bridge']);
        } elseif ($data['type'] == 'bond') {
            $data['parts'] = str_replace(',', ', ', $data['bond']);
        } elseif ($data['type'] == 'vlan') {
            $data['parts'] = $data['vlan'];
        } else {
            $data['parts'] = '';
        }

        $actions = array();

        $actions[] = $view->translate("Action_create_${data['type']}", $data);
        if ($data['bootproto'] === 'dhcp') {
            $actions[] = $view->translate('Action_use_dhcp', $data);
        } elseif ($data['bootproto'] === 'none') {
            $actions[] = $view->translate('Action_set_static_ip', $data);
            if ($data['gateway']) {
                $actions[] = $view->translate('Action_use_gateway', $data);
            } else {
                $actions[] = $view->translate('Action_use_no_gateway', $data);
            }
        }
        $actions[] = $view->translate("Action_create_role", $data);
        return $actions;
    }

    private function getParts($device)
    {
        $state = $this->parameters->getArrayCopy();
        if ($state['type'] === 'bridge') {
            return explode(',', $state['bridge']);
        } elseif ($state['type'] === 'bond') {
            return explode(',', $state['bond']);
        } elseif ($state['type'] === 'vlan') {
            return explode(',', $state['vlan']);
        }
        return array();
    }

    public function process()
    {
        parent::process();
        if ($this->getRequest()->isMutation()) {
            $ndb = $this->getPlatform()->getDatabase('networks');
            $state = $this->parameters->getArrayCopy();

            $props = array('role' => $state['role']);

            if ($state['bootproto'] === 'none') {
                $props['bootproto'] = 'none';
                $props['ipaddr'] = $state['ipaddr'];
                $props['netmask'] = $state['netmask'];
                $props['gateway'] = $state['gateway'];
            } elseif ($state['bootproto'] === 'dhcp') {
                $props['bootproto'] = 'dhcp';
                $props['ipaddr'] = '';
                $props['netmask'] = '';
                $props['gateway'] = '';
            }
            foreach ($this->getParts($state['device']) as $key) {
                if ($state['type'] === 'bridge') {
                    $ndb->delProp($key, array('master', 'ipaddr', 'netmask', 'gateway', 'vlan'));
                    $ndb->setProp($key, array('role' => 'bridged', 'bootproto' => 'none', 'bridge' => $state['device']));
                } elseif ($state['type'] === 'bond') {
                    $ndb->delProp($key, array('bridge', 'ipaddr', 'netmask', 'gateway', 'vlan'));
                    $ndb->setProp($key, array('role' => 'slave', 'bootproto' => 'none', 'master' => $state['device']));
                }
            }
            $this->getPlatform()->getDatabase('networks')->setKey($state['device'], $state['type'], $props);
            $this->getAdapter()->flush();
            $this->getPlatform()->signalEvent('interface-update &');
        }
    }

    public function prepareView(\Nethgui\View\ViewInterface $view)
    {
        parent::prepareView($view);
        $view['values'] = $this->parameters->getArrayCopy();
        $view['actions'] = $this->getActionsText($view);
    }

}

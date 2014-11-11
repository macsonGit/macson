<?php

namespace Drufony\CoreBundle\Twig;

use Drufony\CoreBundle\Model\Menu;

class MenuGeneratorExtension extends \Twig_Extension
{
    public function getFilters() {
        return array(
            new \Twig_SimpleFilter('formatedMenu', array($this, 'getFormatedMenu')),
        );
    }

    /**
     * Giving a array of parents an children generated with Menu::getMenu
     * return html code with menus formated
     *
     * @param array $parents
     * @param array $children
     * @return string
     */
    public static function getFormatedMenu($parents, $children) {

        $result  = '';
        $result .= '<ul>';
        foreach($parents as $parent) {
            $result  .= '<li>' . $parent['itemId'] . ' ' . Menu::getLinkText($parent['linkText']);
            $parentId = $parent['itemId'];

            if(!empty($children[$parentId])) {
                $result     .= '<ul>';
                $parentStack = array();

                $option = array_shift($children[$parentId]);
                while (count($parentStack) > 0 || $option) {

                    if (!$option && (count($parentStack) > 0)) {
                        //Close html if parents stored
                        $parentId = array_pop($parentStack);
                        $result .= '</ul>';
                        $result .= '</li>';
                    }
                    else if (!empty($children[$option['itemId']])) {
                        //Open html for element with children
                        $result  .= '<li>' . $option['itemId'];
                        $result  .= '<ul>';
                        array_push($parentStack, $parentId);
                        $parentId = $option['itemId'];
                    }
                    else {
                        //Element with no children
                        $result .= '<li>' . $option['itemId'] . '</li>';
                    }

                    $option = array_shift($children[$parentId]);
                }
                $result .= '</ul>';
            }
            $result .= '</li>';
        }
        $result .= '</ul>';

        return $result;
    }

    public function getName()
    {
        return 'MenuGeneratorExtension';
    }

}

<?php

/**
 * This file is part of the SgDatatablesBundle package.
 *
 * (c) stwe <https://github.com/stwe/DatatablesBundle>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sg\DatatablesBundle\Datatable\Column;

use Sg\DatatablesBundle\Datatable\Action\Action;
use Sg\DatatablesBundle\Datatable\HtmlContainerTrait;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Closure;
use Exception;

/**
 * Class ActionColumn
 *
 * @package Sg\DatatablesBundle\Datatable\Column
 */
class ActionColumn extends AbstractColumn
{
    /**
     * This Column has a 'start_html' and a 'end_html' option.
     * <startHtml> action1 action2 actionX </endHtml>
     */
    use HtmlContainerTrait;

    /**
     * The Actions container.
     * A required option.
     *
     * @var array
     */
    protected $actions;

    //-------------------------------------------------
    // ColumnInterface
    //-------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function dqlConstraint($dql)
    {
        return null === $dql ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function isSelectColumn()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'SgDatatablesBundle:column:column.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function addDataToOutputArray(array &$row)
    {
        $actionRowItems = array();

        /** @var Action $action */
        foreach ($this->actions as $action) {
            $actionRowItems[$action->getRoute()] = $action->callRenderIfClosure($row);
        }

        $row['sg_datatables_actions'][$this->getIndex()] = $actionRowItems;
    }

    /**
     * {@inheritdoc}
     */
    public function renderContent(array &$row)
    {
        $parameters = array();

        /** @var Action $action */
        foreach ($this->actions as $action) {
            $routeParameters = $action->getRouteParameters();
            if (is_array($routeParameters)) {
                foreach ($routeParameters as $key => $value) {
                    if (isset($row[$value])) {
                        $parameters[$key] = $row[$value];
                    } else {
                        $parameters[$key] = $value;
                    }
                }
            }
            elseif ($routeParameters instanceof Closure) {
                $parameters = call_user_func($routeParameters, $row);
            }
        }

        $row[$this->getIndex()] = $this->twig->render(
            'SgDatatablesBundle:render:action.html.twig',
            array(
                'actions' => $this->actions,
                'set_route_parameters' => $parameters,
                'render_if_actions' => $row['sg_datatables_actions'][$this->index],
                'start_html_container' => $this->startHtml,
                'end_html_container' => $this->endHtml,
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType()
    {
        return 'action';
    }

    //-------------------------------------------------
    // Options
    //-------------------------------------------------

    /**
     * Config options.
     *
     * @param OptionsResolver $resolver
     *
     * @return $this
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->remove('data');
        $resolver->remove('default_content');

        // the 'orderable' option is removed, but via getter it returns 'false' for the view
        $resolver->remove('orderable');

        $resolver->remove('order_data');
        $resolver->remove('order_sequence');

        // the 'searchable' option is removed, but via getter it returns 'false' for the view
        $resolver->remove('searchable');

        $resolver->remove('join_type');
        $resolver->remove('type_of_field');

        $resolver->setRequired(array('actions'));

        $resolver->setDefaults(array(
            'start_html' => null,
            'end_html' => null,
        ));

        $resolver->setAllowedTypes('actions', 'array');
        $resolver->setAllowedTypes('start_html', array('null', 'string'));
        $resolver->setAllowedTypes('end_html', array('null', 'string'));

        return $this;
    }

    //-------------------------------------------------
    // Getters && Setters
    //-------------------------------------------------

    /**
     * Get actions.
     *
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Set actions.
     *
     * @param array $actions
     *
     * @return $this
     * @throws Exception
     */
    public function setActions(array $actions)
    {
        if (count($actions) > 0) {
            foreach ($actions as $action) {
                $newAction = new Action($this->datatableName);
                $this->actions[] = $newAction->set($action);
            }
        } else {
            throw new Exception('ActionColumn::setActions(): The actions array should contain at least one element.');
        }

        return $this;
    }

    /**
     * Get orderable.
     *
     * @return bool
     */
    public function getOrderable()
    {
        return false;
    }

    /**
     * Get searchable.
     *
     * @return bool
     */
    public function getSearchable()
    {
        return false;
    }
}

<?php

namespace Group\Common;

class Paginator {

    protected $itemCount;

    protected $perPageCount;

    protected $currentPage;

    protected $pageRange = 6;

    protected $baseUrl;

    protected $pageKey = 'page';

    public function __construct ($request, $total, $perPage = 20) {
        $this->setItemCount($total);
        $this->setPerPageCount($perPage);

        $page = (int) $request->query->get('page');
        $maxPage = ceil($total / $perPage) ? : 1;
        $this->setCurrentPage($page <= 0 ? 1 : ($page > $maxPage ? $maxPage : $page));

        $this->setBaseUrl($request->server->get('REQUEST_URI'));

    }

    public function setItemCount ($count) {
        $this->itemCount = $count;
        return $this;
    }

    public function setPerPageCount ($count) {
        $this->perPageCount = $count;
        return $this;
    }

    public function getPerPageCount () {
        return $this->perPageCount;
    }

    public function setCurrentPage ($page) {
        $this->currentPage = $page;
        return $this;
    }

    public function setPageRange ($range) {
        $this->pageRange = $range;
        return $this;
    }

    public function setBaseUrl ($url) {
        $template = '';

        $urls = parse_url($url);
        $template .= empty($urls['scheme']) ? '' : $urls['scheme'] . '://';
        $template .= empty($urls['host']) ? '' : $urls['host'];
        $template .= empty($urls['path']) ? '' : $urls['path'];

        if (isset($urls['query'])) {
            parse_str($urls['query'], $queries);
            $queries['page'] = '..page..';
        } else {
            $queries = array('page' => '..page..');
        }
        $template .= '?' . http_build_query($queries);

        $this->baseUrl = $template;
    }

    public function getPageUrl ($page) {
        return str_replace('..page..', $page, $this->baseUrl);
    }

    public function getPageRange () {
        return $this->pageRange;
    }

    public function getCurrentPage () {
        return $this->currentPage;
    }

    public function getFirstPage () {
        return 1;
    }

    public function getLastPage () {
        return ceil($this->itemCount / $this->perPageCount);
    }

    public function getPreviousPage () {
        $diff = $this->getCurrentPage() - $this->getFirstPage();
        return $diff > 0 ? $this->getCurrentPage() - 1 : $this->getFirstPage();
    }

    public function getNextPage () {
        $diff = $this->getLastPage() - $this->getCurrentPage();
        return $diff > 0 ? $this->getCurrentPage() + 1 : $this->getLastPage();
    }

    public function getOffsetCount () {
        return ($this->getCurrentPage() - 1) * $this->perPageCount;
    }

    public function getItemCount () {
        return $this->itemCount;
    }

    public function getPages () {
        $previousRange = round($this->getPageRange() / 2);
        $nextRange = $this->getPageRange() - $previousRange - 1;

        $start = $this->getCurrentPage() - $previousRange;
        $start = $start <= 0 ? 1 : $start;

        $pages = range($start, $this->getCurrentPage());

        $end = $this->getCurrentPage() + $nextRange;
        $end = $end > $this->getLastPage() ? $this->getLastPage() : $end;

        if ($this->getCurrentPage() + 1 <= $end) {
            $pages = array_merge($pages, range($this->getCurrentPage() + 1, $end));
        }
        return $pages;
    }
}
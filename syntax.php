<?php
/**
 * DokuWiki Plugin Pdfjs (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Sahara Satoshi <sahara.satoshi@gmail.com>
 * @author  Szymon Olewniczak <solewniczak@rid.pl>
 *
 * SYNTAX: {{pdfjs [size] > mediaID?zoom|title }}
 *
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once DOKU_PLUGIN . 'syntax.php';

class syntax_plugin_pdfjs extends DokuWiki_Syntax_Plugin {

    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'normal';
    }

    public function getSort() {
        return 305;
    }

    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{pdfjs.*?>.*?}}', $mode, 'plugin_pdfjs');
    }

    /*
     * @var possible zoom values
     *
     * @see https://github.com/mozilla/pdf.js/wiki/Viewer-options
     */
    protected $zoom_opts = array(
        'auto', 'page-actual', 'page-fit', 'page-width',
        '50', '75', '100', '125', '150', '200', '300', '400'
    );
    protected $display_opts = array('tab', 'embed');

    /**
     * handle syntax
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {

        $opts = array( // set default
                       'id'      => '',
                       'title'   => '',
                       'width'   => '100%',
                       'height'  => '600px',
                       'zoom'    => 'auto',
                       'display' => 'embed',
        );

        list($params, $media) = explode('>', trim($match, '{}'), 2);

        // Handle media parameters (linkId and title)
		$media_parts = explode('|', $media, 2);
			$link = $media_parts[0];
			$title = $media_parts[1] ?? 'Default Title';  // Default-Value if title is empty

		$link_parts = explode('?disp=', $link, 2);
			$idzoom = $link_parts[0];
			$display = trim($link_parts[1] ?? '');  //  Default-Value if display is empty

		// Get the display
		if ($display) {
			if (in_array($display, $this->display_opts)) {
			$opts['display'] = $display;
			} else {
				msg('pdfjs: unknown display: ' . $display, -1);
			}
		}

		// Get the zoom
		$idzoom_parts = explode('?', $idzoom, 2);
			$id = $idzoom_parts[0];
			$zoom = $idzoom_parts[1] ?? '';  // Default-Value if zoom is empty

        if($zoom) {
            if(in_array($zoom, $this->zoom_opts)) {
                $opts['zoom'] = $zoom;
            } else {
                msg('pdfjs: unknown zoom: ' . $zoom, -1);
            }
        }

        // handle viewer parameters
        $params = trim(substr($params, strlen('pdfjs')));
        $size   = explode(',', $params, 2);
        //only height
        if(count($size) == 1 && $size[0] != "") {
            $opts['height'] = preg_replace('/\s/', '', $size[0]);

            //width, height
        } else if(count($size) == 2) {
            $opts['width']  = preg_replace('/\s/', '', $size[0]);
            $opts['height'] = preg_replace('/\s/', '', $size[1]);
        }

        //add default px unit
        if(is_numeric($opts['width'])) $opts['width'] .= 'px';
        if(is_numeric($opts['height'])) $opts['height'] .= 'px';

        $opts['id'] = trim($id);
        if(!empty($title)) $opts['title'] = trim($title);

        return array($state, $opts);
    }

    public function render($format, Doku_Renderer $renderer, $data) {

        if($format != 'xhtml') return false;

        list($state, $opts) = $data;
        if($opts['id'] == '') return false;

        $html          = $this->_html_embed_pdfjs($opts);
        $renderer->doc .= $html;

        return true;
    }

    /**
     * Generate html for syntax {{pdfjs>}}
     *
     */
    private function _html_embed_pdfjs($opts) {
        // make reference link
        $src = DOKU_BASE . 'lib/plugins/pdfjs/pdfjs/web/viewer.html';
        $src .= '?file=' . rawurlencode(ml($opts['id']));
        if($opts['display'] == 'tab') {
            $html = '<a href="' . $src . '" class="media mediafile mf_pdf"';
            $html .= 'target="_blank"';
            $html .= '>' . $opts['title'] . '</a>';
        } else {
            if($opts['zoom']) $src .= '#zoom=' . $opts['zoom'];
            $html = '<iframe class="plugin__pdfjs" src="' . $src . '"';
            $html .= ' style="';
            if($opts['width']) $html .= ' width: ' . $opts['width'] . ';';
            if($opts['height']) $html .= ' height: ' . $opts['height'] . ';';
            $html .= ' border: none;';
            $html .= '"></iframe>' . NL;
        }

        return $html;
    }

}

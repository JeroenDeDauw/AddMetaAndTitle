<?php
/*
*
* Extension homepage is at  http://www.mediawiki.org/wiki/Extension:Add_HTML_Meta_and_Title
*
* --------------- Begin Jim R. Wilson's License Data --------------------------------------------------------
 * Copyright (c) 2007 Jim R. Wilson
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy 
 * of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights to 
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of 
 * the Software, and to permit persons to whom the Software is furnished to do 
 * so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all 
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES 
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND 
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, 
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING 
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR 
 * OTHER DEALINGS IN THE SOFTWARE. 
 * --------------- End of Jim R. Wilson's License Data --------------------------------------------------------
 */

/* Version 0.5a Modified by Mic,
 Added support for newer versions ie. 1.1.8
 */


# Confirm MW environment
if (defined('MEDIAWIKI')) {

# Credits
	$wgExtensionCredits['parserhook'][] = array(
		'name'=>'Add_HTML_Meta_and_Title',
		'author'=>'Vladimir Radulovski, Jim Wilson, Mic',
		'url'=>'https://www.mediawiki.org/wiki/Extension:Add_HTML_Meta_and_Title',
		'description'=> htmlentities ('Adds the <seo title="word1,word2,..." metakeywords="word1,word2,..." metadescription="word1,word2,..." /> tag so you can add to the meta keywords and HTML-title of a wiki-page. If you are lazy just use the short version: <seo title="1 , 2" metak="m1 m2,m3" metad="word1, word2"  /> .'),
		'version'=>'0.5.1'
	);

# Add Extension Function
	$wgExtensionFunctions[] = 'setupSEOParserHooks';



	/**
	 * Sets up the MetaKeywordsTag Parser hook and system messages
	 */
	function setupSEOParserHooks() {
		global $wgParser, $wgExtensionMessages;
# meta if empty
		$wgParser->setHook( 'seo', 'renderSEO' );
		$wgExtensionMessages['seo-empty-attr'] ='Error: &lt;seo&gt; tag must contain at least one non-empty &quot;title&quot; or &quot;metak(eywords)&quot; or &quot;metad(escription)&quot; attribute.';
		return true;
	}

	/**
	 * Renders the <keywords> tag.
	 * @param String $text Incomming text - should always be null or empty (passed by value).
	 * @param Array $params Attributes specified for tag - must contain 'content' (passed by value).
	 * @param Parser $parser Reference to currently running parser (passed by reference).
	 * @return String Always empty.
	 */
	function renderSEO( $text, array $params, Parser $parser, PPFrame $frame ) {


		# Short-circuit with error message if content is not specified.
		$emt="";



		if  ( (isset($params['title']))             ||
			(isset($params['metak']))             ||
			(isset($params['metad']))             ||
			(isset($params['metakeywords']))         ||
			(isset($params['metadescription']))
		)
		{
			if  (isset($params['title'])) {$emt .= "<!-- ADDTITLE ".base64_encode($params['title'])." -->";}
			if  (isset($params['metak']))        {$emt .= "<!-- ADDMETAK ".base64_encode($params['metak'])." -->";}
			if  (isset($params['metakeywords'])) {$emt .= "<!-- ADDMETAK ".base64_encode($params['metakeywords'])." -->";}
			if  (isset($params['metad']))           {$emt .= "<!-- ADDMETAD ".base64_encode($params['metad'])." -->";}
			if  (isset($params['metadescription'])) {$emt .= "<!-- ADDMETAD ".base64_encode($params['metadescription'])." -->";}

			return $emt; //$encoded_metas_and_title;

		}
		else


		{return
			'<div class="errorbox">'.
			wfMsgForContent('seo-empty-attr').
			'</div>';
		}



	}


# Attach post-parser hook to extract metadata and alter headers
	$wgHooks['OutputPageBeforeHTML'][] = 'insertMeta';
#$wgHooks['BeforePageDisplay'][] = 'insertMeta';
	$wgHooks['BeforePageDisplay'][] = 'insertTitle';
#$wgHooks['OutputPageBeforeHTML'][] = 'insertTitle';

	/**
	 * Adds the <meta> keywords to document head.
	 * Usage: $wgHooks['OutputPageBeforeHTML'][] = 'insertMetaKeywords';
	 * @param OutputPage $out Handle to an OutputPage object - presumably $wgOut (passed by reference).
	 * @param String $text Output text.
	 * @return Boolean Always true to allow other extensions to continue processing.
	 */


	function insertTitle ( $out ) {

		global $wgSitename;
		# Extract meta keywords
// public function getHTML() { return $this->mBodytext; }

		if (preg_match_all(
			'/<!-- ADDTITLE ([0-9a-zA-Z\\+\\/]+=*) -->/m',
			$out->mBodytext,
			$matches)===false
		) return true;
		$data = $matches[1];
//    print_r ($data) ;
		# Merge keyword data into OutputPage as meta tags
		foreach ($data as $item) {
			$content = @base64_decode($item);
			$content = htmlspecialchars($content, ENT_QUOTES);

			if ($content) {
//        print "DA $content\n";
				$new_title = $out->mHTMLtitle;
				$new_title = "$content - ".$wgSitename;
				$out->setHTMLTitle( $new_title );
			}
			else {
//        print "TZ\n";
			}

		}


		return true;

	}

	function insertMeta( $out, $text ) {

		# Extract meta keywords
		if (preg_match_all(
			'/<!-- ADDMETAK ([0-9a-zA-Z\\+\\/]+=*) -->/m',
			$text,
			$matches)===false
		) return true;
		$data = $matches[1];

		# Merge keyword data into OutputPage as meta tags
		foreach ($data AS $item) {
			$content = @base64_decode($item);
			$content = htmlspecialchars($content, ENT_QUOTES);

			if ($content) {
				$out->addMeta( 'keywords', $content );
			}

		}
// Now for desc

		# Extract meta keywords
		if (preg_match_all(
			'/<!-- ADDMETAD ([0-9a-zA-Z\\+\\/]+=*) -->/m',
			$text,
			$matches)===false
		) return true;
		$data = $matches[1];

		# Merge keyword data into OutputPage as meta tags
		foreach ($data AS $item) {
			$content = @base64_decode($item);
			$content = htmlspecialchars($content, ENT_QUOTES);
#preg_replace($pattern, $replacement, $string);

			if ($content) {
				$out->addMeta( 'description', $content );
			}

		}

		return true;
	}

} # End MW env wrapper
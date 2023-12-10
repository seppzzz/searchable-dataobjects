<?php

namespace seppzzz\SearchableDataObjects;

use SilverStripe\CMS\Controllers\ModelAsController;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Search\SearchForm;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\Connect\MySQLDatabase;
use SilverStripe\SQLite\SQLite3Database;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\PaginatedList;


use SilverStripe\Control\Cookie;

use SilverStripe\Dev\Debug;
use SilverStripe\Dev\Backtrace;


use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLDelete;

/**
 * Extension to provide a search interface when applied to ContentController
 *
 * @package cms
 * @subpackage search
 */
class MyCustomSearch extends Extension
{

    /**
     * @var int the number of items for each page, used for pagination
     */
    private static $items_per_page = 10;

    /**
     * either 'this' for the current page (owner) or a page / controller, e.g. 'SearchPage'
     * @var string
     */
    private static $search_controller = 'SearchPage';

    private static $allowed_actions = array(
        'SearchForm',
    );
	
	

    /**
     * Site search form
     */
    public function SearchForm()
    {
        $form = new SearchForm($this->getControllerForSearchForm(), 'SearchForm', $this->getSearchFields(), $this->getSearchActions());
        return $form;
    }

    /**
     * generates the fields for the SearchForm
     * @uses updateSearchFields
     * @return FieldList
     */
    public function getSearchFields()
    {
        $searchText = _t('SearchForm.SEARCH', 'Search');

        if ($this->owner->request && $this->owner->request->getVar('Search')) {
            $searchText = $this->owner->request->getVar('Search');
        }

        $fields = new FieldList(
           // new TextField('Search', false, $searchText)
        );

        $this->owner->extend('updateSearchFields', $fields);

        return $fields;
    }
	
	
	public function cleanQuery($query)
	{
		$query = preg_replace('/[^ÄÖÜA-Za-zäüößáéíóúàèìòù0-9 \+\-]/u', '', $query);	
		$query = preg_replace('/([\+|\-])\1{1,}/', "$1", $query);
		$query = preg_replace('/([\+|\-])(?![ÄÖÜA-Za-zäüößáéíóúàèìòù])/', '', $query);
		
		return $query;
	}

    /**
     * generates the actions of the SearchForm
     * @uses updateSearchActions
     * @return FieldList
     */
    public function getSearchActions()
    {
        
		$searchText = ''; //_t('SearchForm.SEARCH', 'Search');
		
		if (Controller::curr()->getRequest()->getVar('Search')){
			if(Controller::curr()->getRequest()->getVar('Search') == $searchText){
				$searchText = '';
			}else{
				if(Controller::curr()->getRequest()->getVar('Search') == '' || !Controller::curr()->getRequest()->getVar('Search')){
					$searchText = '';
				}else{
					$searchText = Controller::curr()->getRequest()->getVar('Search');
				}
			}
		}
		
		//$searchText = preg_replace('/[^ÄÖÜA-Za-zäüößáéíóúàèìòù0-9 \+\-]/u', '', $searchText);	
		//$searchText = preg_replace('/(\+){2,}/', "", $searchText);
		//$searchText = preg_replace('/\+(?![^ÄÖÜA-Za-zäüößáéíóúàèìòù])/', '', $searchText);
		//$searchText = preg_replace('/([\+|\-])\1{1,}/', "$1", $searchText);
		
		$searchText = $this->cleanQuery($searchText);
		//Debug::show($searchText);
			
		$actions = new FieldList(
		new LiteralField('', ' <div class="input-group text-right mb-3 ">'), 
			$searchField = new TextField('Search', false, $searchText), //$searchText
			
			new LiteralField('', '<div class="input-group-append ">'), 
			
			//new LiteralField('', '<button type="button" class="btn mybtn-light ajaxmodal" data-id=""  data-toggle="modal" data-target="#modal-container" data-template="DefaultModal" data-var="SiteConfig_getMySearchInfo">&nbsp;?&nbsp;</button>'),
			
			/*
			new LiteralField('', '<div class="input-group-text">'),
				$checkBField = new CheckboxField('CB'),
			new LiteralField('', '</div>'),
			*/
			
			$butt = new FormAction('results', 'SUCHEN'),
			
			new LiteralField('', ' </div></div>')
		);
		
		$butt->addExtraClass('btn mybtn-outline-success');
		$butt->setAttribute('type', 'submit');
		$butt->setAttribute('name', 'action_results');
		$searchField->addExtraClass('form-control ');
		$searchField->removeExtraClass('text');
		$searchField->setAttribute('autocomplete', 'off');
		$searchField->setAttribute('required', 'true');
		$searchField->setAttribute('pattern', '[A-Za-zäöüÄÖÜß+- -\'`´]{4,}');
		$searchField->setAttribute('placeholder', Config::inst()->get('CustomSearch', 'PlaceHolderText'));
        $this->owner->extend('updateSearchActions', $actions);

        return $actions;
    }

    /**
     *
     * @return ContentController
     */
    public function getControllerForSearchForm()
    {
        $controllerName = Config::inst()->get('CustomSearch', 'search_controller');

        if ($controllerName == 'this') {
            return $this->owner;
        }

        if (class_exists($controllerName)) {
            $obj = Object::create($controllerName);

            if ($obj instanceof SiteTree && $page = $controllerName::get()->first()) {
                return ModelAsController::controller_for($page);
            }

            if ($obj instanceof Controller) {
                return $obj;
            }
        }

        //fallback:
        //@todo: throw notice
        return $this->owner;
    }
	
	
	public function results($data, $form, $request)
    {
        $data = array(
                'Results' => $this->getSearchResults($request, $data),
                'Query' => $form->getSearchQuery(),
                'Title' => _t('CustomSearch.SEARCHRESULTS', 'Risultati della ricerca')
        );
        return $this->owner->customise($data)->renderWith(array('Page_results', 'Page'));
    }

    /**
     * Check if Fulltext search is supported
     * @return boolean True if supported
     */
    public static function isFulltextSupported()
    {
        $conn = DB::get_conn();

        if ($conn instanceof MySQLDatabase) {
            return true;
        }

        // check SQLite and enabled
        if ($conn instanceof SQLite3Database) {
            $checkOption = "sqlite_compileoption_used('IsFullTextInstalled')";
            $result = DB::query("SELECT $checkOption")->first();
            if (isset($result[$checkOption]) && $result[$checkOption]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process and render search results.
     *
     * @param SS_HTTPRequest $request Request generated for this action
     * @param array $data The raw request data submitted by user
     * @param SearchForm $form The form instance that was submitted
     */
    public function getSearchResults($request, $data = [], $form = null)
    {
        
		
		
		$list = new ArrayList();
		// get search query
        $q = (isset($data['Search'])) ? $data['Search'] : $request->getVar('Search');
        $keywords = Convert::raw2sql(trim($q));
		
		$keywords = $this->cleanQuery($keywords);

        // Nothing to search
        if(empty($keywords)) {
            return false;
        }
		
		$words = preg_split("/[\s\n\r]/", $q);
		foreach ($words as $value) 
		{
			// Skip it is common word
			if (isset($hashWord[$value]))  continue;
			// Skip if it is numeric
			if (is_numeric($value))  continue;
			// Skip if word contains less than 4 digits
			if (strlen($value) < 4)  continue;
			
			//$value = preg_replace('/\+(?![^ÄÖÜA-Za-zäüößáéíóúàèìòù])/', '', $value);
			$highlightwords[] = preg_replace('/[^ÄÖÜA-Za-zäüößáéíóúàèìòù0-9 \-]/u', '', $value);
			
		}
		
		if(!isset($highlightwords)){
			return false;
		}
		
		$input = implode(" ", $highlightwords);
		$input = Convert::raw2sql(trim($input));
		
		$andProcessor = function($matches) {
			return " +" . $matches[2] . " +" . $matches[4] . " ";
		};

		$notProcessor = function($matches) {
			return " -" . $matches[3];
		};

        $keywords = preg_replace_callback('/()("[^()"]+")( and )("[^"()]+")()/i', $andProcessor, $keywords);
        $keywords = preg_replace_callback('/(^| )([^() ]+)( and )([^ ()]+)( |$)/i', $andProcessor, $keywords);
        $keywords = preg_replace_callback('/(^| )(not )("[^"()]+")/i', $notProcessor, $keywords);
        $keywords = preg_replace_callback('/(^| )(not )([^() ]+)( |$)/i', $notProcessor, $keywords);
        
        $keywords = $this->addStarsToKeywords($keywords);
        
        $start = 0; //isset($_GET['start']) ? (int)$_GET['start'] : 0;
        $pageLength = 10;
        
		
		$sql = new SQLSelect();
		$sql->setDistinct(true);
		$sql->setFrom('SearchableDataObjects');
		$sql->addSelect("(( 2 * (MATCH (`SearchableDataObjects`.`Title`, `SearchableDataObjects`.`Content`) AGAINST ('{$keywords}' IN BOOLEAN MODE)))  ) AS Relevance");
		//$sql->addSelect("( MATCH (`SearchableDataObjects`.`Title`, `SearchableDataObjects`.`Content`) AGAINST ('{$keywords}' )  ) AS Relevance");
		$sql->setWhere("(MATCH (`SearchableDataObjects`.`Title`,`SearchableDataObjects`.`Content`) AGAINST ('{$keywords}' IN BOOLEAN MODE))");
		$sql->setOrderBy("Relevance","DESC");
		//$sql->setOrderBy(array("Relevance" => "DESC", "Created" => "DESC"));
		$totalCount = $sql->count();
		//$sql->setLimit($pageLength, $start);
		$results = $sql->execute();
		//Debug::show($results);
		
		
		
		
		
		
		
		/*
		$query = "SELECT * FROM table WHERE";
		$conds = array();
		foreach ($words as $val) {
			$conds[] = "table.keywords LIKE '%".$val."%'";
		}
		$query .= implode(' OR ', $conds);
		*/
		
		
		$inputWords = $this->cleanQuery($input);
		$inputWords = explode(" " , trim($inputWords));
		//Debug::dump($inputWords);
		
		$query = "SELECT * FROM \"SearchableDataObjects\" WHERE";
		$conds = array();
		foreach ($inputWords as $val) {
			$conds[] = "( \"Content\") LIKE '%".$val."%'";
		}
		$query .= implode(' OR ', $conds);
		
		
		
        $results2 = DB::query($query);
		
		$result = new ArrayList();
		$result->merge($results);
		$result->merge($results2);
		$result->removeDuplicates('ID');
		
		foreach ($result as $row) {
            $do = DataObject::get_by_id($row->ClassName, $row->ObjectID);
			
            if (is_object($do) && $do->exists() ) {
				
				if($do->Link()){
					//Debug::dump($do);
					$do->RTitle = $this->highlight(($row->Title), $input);
					$do->RContent = $this->googlifyMe($row->Content, $input, '<span class=\'highlight_search\'>%s</span>');
                	$list->push($do);
					
				}else{
					
					//row delete
					$query = SQLDelete::create()
						->setFrom('"SearchableDataObjects"')
						->setWhere(array('"SearchableDataObjects"."ID"' => $row->ID, '"SearchableDataObjects"."ClassName"' => $row->ClassName));
					$query->execute();
					
				}
            }
        }

        $pageLength = Config::inst()->get('seppzzz\SearchableDataObjects\CustomSearch', 'items_per_page');
        $ret = new PaginatedList($list, $request);
        $ret->setPageLength($pageLength);

        return $ret;

		
		
		
		
		
		
        
		
		/*foreach ($results as $row) {
            $do = DataObject::get_by_id($row['ClassName'], $row['ID']);
			
            if (is_object($do) && $do->exists() ) {
				
				if($do->Link()){
					//Debug::dump($do);
					$do->RTitle = $this->highlight(($row['Title']), $input);
					$do->RContent = $this->googlifyMe($row['Content'], $input, '<span class=\'highlight_search\'>%s</span>');
                	$list->push($do);
					
				}else{
					
					//row delete
					$query = SQLDelete::create()
						->setFrom('"SearchableDataObjects"')
						->setWhere(array('"SearchableDataObjects"."ID"' => $row['ID'], '"SearchableDataObjects"."ClassName"' => $row['ClassName']));
					$query->execute();
					
				}
            }
        }

        $pageLength = Config::inst()->get('seppzzz\SearchableDataObjects\CustomSearch', 'items_per_page');
        $ret = new PaginatedList($list, $request);
        $ret->setPageLength($pageLength);

        return $ret;*/
    }
	
	
	
	protected function addStarsToKeywords($keywords)
	{
        if(!trim($keywords)) return "";
        // Add * to each keyword
        $splitWords = preg_split("/ +/" , trim($keywords));
        //while(list($i,$word) = each($splitWords)) {
		foreach ($splitWords as $i => $word){
            if($word[0] == '"') {
               // while(list($i,$subword) = each($splitWords)) {
				foreach ($splitWords as $i => $subword){
                    $word .= ' ' . $subword;
                    if(substr($subword,-1) == '"') break;
                }
            } else {
                $word .= '*';
            }
            $newWords[] = $word;
        }
        return implode(" ", $newWords);
    }
	
	
	
	
	
	
	public function highlight($text, $words, $googlify = false) 
	{
		$wordsAry = explode(" ", $words);
		$wordsCount = count($wordsAry);
		$rtext = '';
		
		for($i=0; $i<$wordsCount; $i++) {
			
			$highlighted_text = "<span style='font-weight:bold;'>$wordsAry[$i]</span>";
			$text = str_ireplace($wordsAry[$i], $highlighted_text, $text);
			$text = preg_replace("/(\p{L}*?)(".preg_quote($wordsAry[$i]).")(\p{L}*)/ui", "<span class='highlight_search'>$2</span>$3", $text);
			
		}
		
		return $text;
	}
	
	

	public function googlifyMe($text, $query, $highlight, $teaserLength = 800, $minGap = 5, $minWordLength = 3) 
	{ 
		
		if(!is_array($query)) { 
			//$query    = preg_replace('/[^\w\s]/',                            ' ', $query); 
			//$query    = preg_replace('/\b\w{0,'.($minWordLength - 1).'}\b/', ' ', $query); 
			//$query    = preg_replace('/\s/',                                 ' ', $query); 
			//$query    = preg_replace('/\s{2,}/',                             ' ', $query);
			$words    = array_unique(explode(' ', trim($query))); 
			$numWords = count($words); 
		} else { 
			$words    = $query; 
			$numWords = count($words); 
		}
		
		 
		if($numWords <= 0) { 
			return false; 
		} 

		$pre      = round(((($teaserLength / $numWords) / 2) - 6 + $minGap), 0); 
		$pre      = $pre < 0 ? 0 : $pre; 
		$searchHi = '/('.join('|', $words).')/iu'; // ie
		$search   = join('|', $words); 
		$search   = '/\b(.{0,'.$pre.'})('.$search.')(.{0,'.$pre.'})\b.{'.$minGap.'}/siu'; //si
		$result   = ''; 
		
		preg_match_all($search, $text, $matches); 
		$wordsDone    = array(); 
		$countMatches = count($matches[0]); 
		
		if($countMatches >= 1){
			for($i = 0; $i < $countMatches; $i++) 
			{ 
				$preWord  = $matches[1][$i]; 
				$postWord = $matches[3][$i]; 
				$word     = $matches[2][$i]; 
				
				$dots = '...';

				if(isset($wordsDone[strtolower($word)])) { 
					continue; 
				} else { 
					$wordsDone[strtolower($word)] = 1; 
				} 
				$tmp = $dots.$preWord.$word.$postWord; 
				$result .= preg_replace_callback($searchHi, function ($matches) use ($highlight) {
					return sprintf($highlight, stripslashes($matches[0]));
				}, $tmp).' ...<br><br>';
			}
		}else{
			$result .= DBField::create_field('HTMLText',$text)->LimitWordCount(30, '');
		}

		return ltrim($result); 
	} 
	

    
}

<?php
/**
 * dokubookmark plugin helper functions
 * Dokuwiki website tagger - act like a weblog
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Robin Gareus <robin@gareus.org>
 * @based_on   http://wiki.splitbrain.org/wiki:tips:weblog_bookmarklet by riny [at] bk [dot] ru
 */ 

  /**
   *  - TODO this should use the dokuwiki template header.
   */
  function printHeader() {
  global $conf;
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
<title>Dokuwiki Website Tagger</title>
<?php tpl_metaheaders()?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="shortcut icon" href="<?php echo DOKU_TPL?>images/favicon.ico" />

<script type="text/javascript">
/* <![CDATA[ */
function initTagEntry() {
    document.getElementById('blogtng__tags').value = 'Bookmark, ';
}
/* ]]> */
</script>
</head>
<body>
<script type="text/javascript">
/* <![CDATA[ */
window.onload=initTagEntry;
/* ]]> */
</script>
<div class="dokuwiki" style="background: #fff; border:0px; color: #000">
<?php 
  }


  /**
   *
   */
  function printFooter() { ?>
</div> <!-- class="dokuwiki" -->
</body>
</html>
<?php 
  }

  function escapeJSstring ($o) {
    return ( # TODO: use JSON ?!
      str_replace("\n", '\\n', 
      str_replace("\r", '', 
      str_replace('\'', '\\\'', 
      str_replace('\\', '\\\\', 
        $o)))));
  }

  function parseWikiIdTemplate($idx, $data) {
    if (empty($data['name'])) {
			$n='';
			# check for single word selection -> use this
			if (!strstr($data['selection'], ' ')) 
				$n=$data['selection'];
			# check if title is a not empty 
			if (empty($n))
				$n=$data['title'];
			# if still empty.. mmh - use URL or use 'noname'
			if (empty($n)) $n='noname';

			#->  replace ': ' and ASCIIfy
			$n=strtr($n, ': ','__');
			# [^\x20-\x7E] or [^A-Za-z_0-9]
			$n=preg_replace('@[^A-Za-z_0-9]@', '', $n);
			$n=preg_replace('@__*@', '_', $n);
			# trim to 64 chars.
			$data['name']=substr($n,0,64);
		}
    # TODO: replace Placeholders alike ../../../inc/common.php pageTemplate() ?!
	  return str_replace("@D@",$data['timestamp'],
           str_replace("@S@",$data['selection'],
           str_replace("@U@",$data['url'],
           str_replace("@N@",$data['name'],
           str_replace("@F@",$data['foo'],
           str_replace("@T@",$data['title'], $idx))))));
  }


  /**
   *
   */
  function printForm ($data, $options, $alltags = NULL) {
    global $ID;
    global $REV;
    global $DATE;
    global $PRE;
    global $SUF;
    global $INFO;
    global $SUM;
    global $lang;
    global $conf;
    global $TEXT;
    global $RANGE;

    $SUM = htmlentities($data['title'], ENT_COMPAT, 'UTF-8');

    echo '<h3>Dokuwiki - add bookmark / weblog entry</h3>';

    if (isset($_REQUEST['changecheck'])) {
        $check = $_REQUEST['changecheck'];
    } elseif(!$INFO['exists']){
        // $TEXT has been loaded from page template
        $check = md5('');
    } else {
        $check = md5($TEXT);
    }
    $mod = md5($TEXT) !== $check;

    $wr = $INFO['writable'] && !$INFO['locked'];
    $include = 'edit';
    if($wr){
        if ($REV) $include = 'editrev';
    }else{
        // check pseudo action 'source'
        if(!actionOK('source')){
            msg('Command disabled: source',-1);
            return;
        }
        $include = 'read';
    }

    global $license;

    $wikitext = parseWikiIdTemplate($data['wikitpl'], $data);
    $id       = parseWikiIdTemplate($data['wikiidtpl'], $data);
    $TEXT = $wikitext;
    $ID   = $id;

    $form = new Doku_Form(array('id' => 'dw__editform', 'action' => $data['baseurl']));
    $form->addHidden('rev', $REV);
    $form->addHidden('date', $DATE);
    $form->addHidden('prefix', $PRE . '.');
    $form->addHidden('suffix', $SUF);
    $form->addHidden('changecheck', $check);


    $dataNew = array('form' => $form,
                     'wr'   => $wr,
                     'media_manager' => true,
                     'target' => (isset($_REQUEST['target']) && $wr &&
                                  $RANGE !== '') ? $_REQUEST['target'] : 'section',
                     'intro_locale' => $include);

    $data = array_merge($data, $dataNew);

    $form->addElement(form_makeOpenTag('p'));
    $form->addElement(form_makeTextField('id', htmlentities(parseWikiIdTemplate($data['wikiidtpl'], $data), ENT_COMPAT, 'UTF-8'), 'Id:', 'i_id'));

    if ($options['preset']) {
      $form->addElement('<br/>&nbsp;Preset:');
      $i=0;
      foreach ($options['presets'] as $n => $ps)  {
        $id_       = parseWikiIdTemplate($ps['id'], $data);
        $wikitext_ = parseWikiIdTemplate($ps['tpl'], $data);

        if ($i>0)
            $form->addElement(',');
        else 
            $form->addElement('&nbsp;');

        $form->addElement('&nbsp;');
        $additionalJs = '';
        if (!empty($wikitext_)) {
            $additionalJs = 'document.getElementById(\'wiki__text\').value=\''.escapeJSstring($wikitext_).'\';';
        }
        $form->addElement(form_makeTag('input', array(
                'type'    => 'button',
                'value'   => $n,
                'class'   => 'button',
                'title'   => $n,
                'onclick' => 'document.getElementById(\'i_id\').value=\''.escapeJSstring($id_).'\';document.getElementById(\'id\').value=\''.escapeJSstring($id_).'\';'.$additionalJs
            )));

        $i++;
      } 
    } ### done Preset Buttons
    $form->addElement(form_makeCloseTag('p'));


    if ($data['target'] !== 'section') {
        // Only emit event if page is writable, section edit data is valid and
        // edit target is not section.
        trigger_event('HTML_EDIT_FORMSELECTION', $data, 'html_edit_form', true);
    } else {
        html_edit_form($data);
    }

    //if (isset($data['intro_locale'])) {
    //    echo p_locale_xhtml($data['intro_locale']);
    //}


    $form->addHidden('target', $data['target']);
    $form->addElement(form_makeOpenTag('div', array('id'=>'wiki__editbar')));
    $form->addElement(form_makeOpenTag('div', array('id'=>'size__ctl')));
    $form->addElement(form_makeCloseTag('div'));
    if ($wr) {
        $form->addElement(form_makeOpenTag('div', array('class'=>'editButtons')));
        if ($options['enable_save']) {
            $form->addHidden('sectoc', getSecurityToken());
            $form->addElement(form_makeTag('input', array(
                'id'        => 'edbtn__save',
                'type'      => 'submit',
                'name'      => 'do[dokubookmark]',
                'value'     => $lang['btn_save'],
                'class'     => 'button',
                'title'     => $lang['btn_save'] . ' [S]',
                'accesskey' => 's',
                'tabindex'  => '4'
            )));

        }
        $form->addElement(form_makeButton('submit', 'preview', $lang['btn_preview'], array('id'=>'edbtn__preview', 'accesskey'=>'p', 'tabindex'=>'5')));
        $form->addElement(form_makeButton('submit', 'draftdel', $lang['btn_cancel'], array('tabindex' => '6', 'onclick' => 'window.close()')));
        $form->addElement(form_makeCloseTag('div'));
        $form->addElement(form_makeOpenTag('div', array('class'=>'summary')));
        $form->addElement(form_makeTextField('summary', $SUM, $lang['summary'], 'edit__summary', 'nowrap', array('size'=>'50', 'tabindex'=>'2')));
        $elem = html_minoredit();
        if ($elem) $form->addElement($elem);
        $form->addElement(form_makeCloseTag('div'));
    }
    $form->addElement(form_makeCloseTag('div'));
    if($wr && $conf['license']){
        $form->addElement(form_makeOpenTag('div', array('class'=>'license')));
        $out  = $lang['licenseok'];
        $out .= ' <a href="'.$license[$conf['license']]['url'].'" rel="license" class="urlextern"';
        if(isset($conf['target']['extern'])) $out .= ' target="'.$conf['target']['extern'].'"';
        $out .= '>'.$license[$conf['license']]['name'].'</a>';
        $form->addElement($out);
        $form->addElement(form_makeCloseTag('div'));
    }

    if ($wr) {
        // sets changed to true when previewed
        echo '<script type="text/javascript" charset="utf-8"><!--//--><![CDATA[//><!--'. NL;
        echo 'textChanged = ' . ($mod ? 'true' : 'false');
        echo '//--><!]]></script>' . NL;
    } ?>
    <div style="width:99%;">

    <div class="toolbar">
    <div id="draft__status"><?php if(!empty($INFO['draft'])) echo $lang['draftdate'].' '.dformat();?></div>
    <div id="tool__bar"><?php if ($wr && $data['media_manager']){?><a href="<?php echo DOKU_BASE?>lib/exe/mediamanager.php?ns=<?php echo $INFO['namespace']?>"
        target="_blank"><?php echo $lang['mediaselect'] ?></a><?php }?></div>

    </div>
    <?php

    html_form('edit', $form);
    print '</div>'.NL;

  }


  /**
   *  - unused javascript redirect/POST - 
   *
   *  - could be made into a non-interactive bookmarklet -
   */
  function printPost($targeturl, $path, $wikiid, $timestamp, $title, $wikitext) {
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<head>
<script type="text/javascript">
/* <![CDATA[ */
function postit() {
  f        = document.createElement('form');              
  f.method = 'post';                                      
  f.action = '<?php echo $targeturl;?>';

  i0       = document.createElement('input');             
  i0.type  = 'hidden';                                      
  i0.name  = 'wikitext';                                      
  i0.value = '<?php echo implode('\n', explode("\n",str_replace("'","\\'",$wikitext)));?>';
                                                          
  i1       = document.createElement('input');
  i1.type  = 'hidden';                                    
  i1.name  = 'do';
  i1.value = 'preview';

  i3       = document.createElement('input');
  i3.type  = 'hidden';                                    
  i3.name  = 'summary';
  i3.value = '<?php echo str_replace("'","\\'",rawurlencode($title));?>';

  i4       = document.createElement('input');
  i4.type  = 'hidden';                                    
  i4.name  = 'sectok';
  i4.value = '<?php echo getSecurityToken();?>';

  f.appendChild(i0);
  f.appendChild(i1);
  f.appendChild(i3);
  f.appendChild(i4);
  b = document.getElementsByTagName('body')[0];
  b.appendChild(f);
  f.submit();
  }
/* ]]> */
</script>
<body onload="postit();">
</body>
</html>
<?php
  }

//Setup VIM: ex: et ts=2 :

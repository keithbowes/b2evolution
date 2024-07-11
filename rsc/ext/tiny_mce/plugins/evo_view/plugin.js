/* global tinymce */

/**
 * b2evolution View plugin.
 */
tinymce.PluginManager.add( 'evo_view', function( editor ) {
		var $ = editor.$,
				selected,
				selectedView,
				Env = tinymce.Env,
				VK = tinymce.util.VK,
				TreeWalker = tinymce.dom.TreeWalker,
				toRemove = false,
				firstFocus = true,
				_noop = function() { return false; },
				isios = /iPad|iPod|iPhone/.test( navigator.userAgent ),
				cursorInterval,
				lastKeyDownNode,
				setViewCursorTries,
				focus,
				execCommandView,
				execCommandBefore,
				toolbar,
				shortTags = [ 'image', 'thumbnail', 'inline', 'button', 'cta', 'like', 'dislike', 'activate', 'unsubscribe' ];


		/**
		 * Returns the node or a parent of the node that has the passed className.
		 *
		 * @param object Node
		 * @param string Class name
		 */
		function getParent( node, className )
		{
			while ( node && node.parentNode )
			{
				if( node.className && ( ' ' + node.className + ' ' ).indexOf( ' ' + className + ' ' ) !== -1 )
				{
					return node;
				}

				node = node.parentNode;
			}

			return false;
		}

		/**
		 * Get view node
		 *
		 * @param object Node
		 */
		function getView( node )
		{
			return getParent( node, 'evo-view-wrap' );
		}


		/**
		 * Edit selected view
		 */
		function editView()
		{
			var viewType;

			if( selectedView )
			{
				viewType = selectedView.getAttribute( 'data-evo-view-type' );
			}

			if( shortTags.indexOf( viewType ) != -1 )
			{
				var shortTag = decodeURIComponent( selected.getAttribute( 'data-evo-view-text' ) );
				evo_item_image_edit( editor.settings.blog_ID, shortTag );
			}
			else
			{
				openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="' + evo_js_lang_loading + '..."></span>',
						'80%', '', true, evo_js_lang_select_image_insert, '', true );
				jQuery.ajax( {
						type: 'POST',
						url: editor.getParam( 'modal_url' ),
						success: function(result)
						{
							//openModalWindow( result, '90%', '80%', true, 'Select image', '' );
							openModalWindow( result, '90%', '80%', true, 'Select image', '', '', '', '', '', function() {
									var target_type, target_ID;
									if( editor.getParam( 'temp_ID' ) == undefined )
									{
										target_type = editor.getParam( 'target_type' );
										target_ID = editor.getParam( 'target_ID' );
									}
									else
									{
										target_type = 'temporary';
										target_ID = editor.getParam( 'temp_ID' );
									}

									evo_link_refresh_list( target_type, target_ID, 'refresh' );
									evo_link_fix_wrapper_height();
								} );
						}
					} );

				return false;
			}
		}


		/**
		 * Stop event
		 *
		 * @param event Event to stop
		 */
		function _stop( event )
		{
			event.stopPropagation();
		}


		/**
		 * Set view cursor location
		 *
		 * @param boolean True to set cursor before the view, False to set cursor after the view
		 * @param object View
		 */
		function setViewCursor( before, view )
		{
			var location = before ? 'before' : 'after';

			deselect();
			editor.selection.setCursorLocation( editor.dom.select( '.evo-view-selection-' + location, view )[0] );
			editor.nodeChanged();
		}


		/**
		 * Handle Enter key on selected view
		 *
		 * @param object View
		 * @param  before
		 * @param {*} key
		 */
		function handleEnter( view, before, key )
		{
			var dom = editor.dom,
					padNode = dom.create( 'p' );

			if( ! ( Env.ie && Env.ie < 11 ) )
			{
				padNode.innerHTML = '<br data-mce-bogus="1">';
			}

			if ( before )
			{
				view.parentNode.insertBefore( padNode, view );
			}
			else
			{
				dom.insertAfter( padNode, view );
			}

			deselect();

			if( before && key === VK.ENTER )
			{
				setViewCursor( before, view );
			}
			else
			{
				editor.selection.setCursorLocation( padNode, 0 );
			}

			editor.nodeChanged();
		}


		/**
		 * Remove selected view from content
		 * @param object View to remove
		 */
		function removeView( view )
		{
			editor.undoManager.transact( function() {
					// erhsatingin: The following line was commented out as it adds an extra paragraph upon removal of view
					//handleEnter( view );
					evo.views.remove( editor, view );
				} );
		}


		/**
		 * Select view
		 *
		 * @param object View node
		 */
		function select( viewNode )
		{
			var clipboard,
					dom = editor.dom;

			if( ! viewNode )
			{
				return;
			}

			if( viewNode !== selected )
			{
				deselect();
				selected = viewNode;
				selectedView = getView( viewNode );
				dom.setAttrib( viewNode, 'data-mce-selected', 1 );
				dom.addClass( selected, 'evo-view-selected' );

				clipboard = dom.create( 'div', {
						'class': 'evo-view-clipboard',
						'contenteditable': 'true'
					}, evo.views.getText( viewNode ) ); //wp.mce.views

				editor.dom.select( '.evo-view-body', viewNode )[0].appendChild( clipboard );

				// Both of the following are necessary to prevent manipulating the selection/focus
				dom.bind( clipboard, 'beforedeactivate focusin focusout', _stop );
				dom.bind( selected, 'beforedeactivate focusin focusout', _stop );

				// select the hidden div
				if ( isios )
				{
					editor.selection.select( clipboard );
				}
				else
				{
					editor.selection.select( clipboard, true );
				}
			}

			editor.nodeChanged();
			editor.fire( 'evo-view-selected', viewNode );
		}


		/**
		 * Deselect a selected view and remove clipboard
		 */
		function deselect()
		{
			var clipboard,
					dom = editor.dom;

			if( selected )
			{
				clipboard = editor.dom.select( '.evo-view-clipboard', selected )[0];
				dom.unbind( clipboard );
				dom.remove( clipboard );

				dom.unbind( selected, 'beforedeactivate focusin focusout click mouseup', _stop );
				dom.setAttrib( selected, 'data-mce-selected', null );
				dom.removeClass( selected, 'evo-view-selected' );
			}

			selected = null;
			selectedView = null;
		}


		/*
		// Check if the `wp.mce` API exists.
		if ( typeof wp === 'undefined' || ! wp.mce ) {
			return {
				getView: _noop
			};
		}
		*/


		/**
		 * Callback for resetViews
		 *
		 * @param string Matched text
		 * @param string viewText
		 * @return string The view text
		 */
		function resetViewsCallback( match, viewText )
		{
			//return '<p>' + window.decodeURIComponent( viewText ) + '</p>';
			return window.decodeURIComponent( viewText );
		}


		/**
		 * Replace the view tags with the view string
		 *
		 * @param string Content string
		 * @return string Replaced content string
		 */
		function resetViews( content )
		{
			return content.replace( /<(?:div|span)[^>]+data-evo-view-text="([^"]+)"[^>]*>(?:[\s\S]+?evo-view-selection-after[^>]+>[^<>]*<\/p>\s*|\.)<\/(?:div|span)>/g, resetViewsCallback )
				.replace( /<p [^>]*?data-evo-view-marker="([^"]+)"[^>]*>[\s\S]*?<\/p>/g, resetViewsCallback );
		}


		/**
		 * Prevent adding undo levels on changes inside a view wrapper
		 */
		editor.on( 'BeforeAddUndo', function( event ) {
				if( event.level.content )
				{
					event.level.content = resetViews( event.level.content );
				}
			} );

		/**
		 * When the editor's content changes, scan the new content for
		 * matching view patterns, and transform the matches into
		 * view wrappers.
		 */
		editor.on( 'BeforeSetContent', function( event ) {
				var node;

				if( ! event.selection )
				{
					evo.views.unbind();
				}

				if( ! event.content )
				{
					return;
				}

				if( ! event.load )
				{
					if( selected )
					{
						removeView( selected );
						deselect();
					}

					node = editor.selection.getNode();
					if( node && node !== editor.getBody() && /^\s*https?:\/\/\S+\s*$/i.test( event.content ) )
					{	// When a url is pasted or inserted, only try to embed it when it is in an empty paragrapgh.
						node = editor.dom.getParent( node, 'p' );

						if( node && /^[\s\uFEFF\u00A0]*$/.test( $( node ).text() || '' ) )
						{	// Make sure there are no empty inline elements in the <p>
							node.innerHTML = '';
						}
						else
						{
							return;
						}
					}
				}

				event.content = evo.views.setMarkers( event.content );
			} );

		/**
		 * When pasting, strip all tags and check if the string is an URL.
		 * Then replace the pasted content with the cleaned URL.
		 */
		editor.on( 'pastePreProcess', function( event ) {
				var pastedStr = event.content;

				if( pastedStr )
				{
					pastedStr = tinymce.trim( pastedStr.replace( /<[^>]+>/g, '' ) );

					if( /^https?:\/\/\S+$/i.test( pastedStr ) )
					{
						event.content = pastedStr;
					}
				}
			} );

		/**
		 * When the editor's content has been updated and the DOM has been
		 * processed, render the views in the document.
		 */
		editor.on( 'SetContent', function() {
				evo.views.render();
			} );

		// Set the cursor before or after a view when clicking next to it.
		editor.on( 'click', function( event ) {
				var x = event.clientX,
						y = event.clientY,
						body = editor.getBody(),
						bodyRect = body.getBoundingClientRect(),
						first = body.firstChild,
						last = body.lastChild,
						firstRect, lastRect, view;

				if( ! first || ! last )
				{
					return;
				}

				firstRect = first.getBoundingClientRect();
				lastRect = last.getBoundingClientRect();

				if( y < firstRect.top && ( view = getView( first ) ) )
				{
					setViewCursor( true, view );
					event.preventDefault();
				}
				else if( y > lastRect.bottom && ( view = getView( last ) ) )
				{
					setViewCursor( false, view );
					event.preventDefault();
				}
				else if( x < bodyRect.left || x > bodyRect.right )
				{
					tinymce.each( editor.dom.select( '.evo-view-wrap' ), function( view ) {
							var rect = view.getBoundingClientRect();

							if( y < rect.top )
							{
								return false;
							}

							if( y >= rect.top && y <= rect.bottom )
							{
								if( x < bodyRect.left )
								{
									setViewCursor( true, view );
									event.preventDefault();
								}
								else if( x > bodyRect.right )
								{
									setViewCursor( false, view );
									event.preventDefault();
								}

								return false;
							}
						} );
				}
			} );

		editor.on( 'init', function() {
				var scrolled = false,
						selection = editor.selection,
						MutationObserver = window.MutationObserver || window.WebKitMutationObserver;

				// When a view is selected, ensure content that is being pasted
				// or inserted is added to a text node (instead of the view).
				editor.on( 'BeforeSetContent', function() {
						var walker, target,
							view = getView( selection.getNode() );

						// If the selection is not within a view, bail.
						if ( ! view ) {
							return;
						}

						if( ! view.nextSibling || getView( view.nextSibling ) )
						{	// If there are no additional nodes or the next node is a
							// view, create a text node after the current view.
							target = editor.getDoc().createTextNode('');
							editor.dom.insertAfter( target, view );
						}
						else
						{	// Otherwise, find the next text node.
							walker = new TreeWalker( view.nextSibling, view.nextSibling );
							target = walker.next();
						}

						// Select the `target` text node.
						selection.select( target );
						selection.collapse( true );
					} );

				editor.dom.bind( editor.getDoc(), 'touchmove', function() {
						scrolled = true;
					} );

				editor.on( 'dblclick', function( event ) {
						var view = getView( event.target );

						// Contain clicks inside the view wrapper
						if( view )
						{
							event.stopImmediatePropagation();
							event.preventDefault();

							if( event.type === 'touchend' && scrolled )
							{
								scrolled = false;
							}
							else
							{
								select( view );
							}

							// Returning false stops the ugly bars from appearing in IE11 and stops the view being selected as a range in FF.
							// Unfortunately, it also inhibits the dragging of views to a new location.

							if( selectedView )
							{
								viewType = selectedView.getAttribute( 'data-evo-view-type' );
							}

							var shortTag = decodeURIComponent( selected.getAttribute( 'data-evo-view-text' ) );
							evo_item_image_edit( editor.settings.blog_ID, shortTag );

							return false;
						}
					} );

				editor.on( 'mousedown mouseup click touchend', function( event ) {
						var view = getView( event.target );

						firstFocus = false;

						// Contain clicks inside the view wrapper
						if( view )
						{
							event.stopImmediatePropagation();
							event.preventDefault();

							if( event.type === 'touchend' && scrolled )
							{
								scrolled = false;
							} else {
								select( view );
							}

							// Returning false stops the ugly bars from appearing in IE11 and stops the view being selected as a range in FF.
							// Unfortunately, it also inhibits the dragging of views to a new location.
							return false;
						}
						else
						{
							if( event.type === 'touchend' || event.type === 'mousedown' )
							{
								deselect();
							}
						}

						if( event.type === 'touchend' && scrolled )
						{
							scrolled = false;
						}
					}, true );

				if( MutationObserver )
				{
					new MutationObserver( function() {
							editor.fire( 'evo-body-class-change' );
						} ).observe( editor.getBody(), {
								attributes: true,
								attributeFilter: ['class']
							} );
				}

				if( tinymce.Env.ie )
				{
					// Prevent resize handles in newer IE
					editor.dom.bind( editor.getBody(), 'controlselect mscontrolselect', function( event ) {
							if ( getView( event.target ) ) {
								event.preventDefault();
							}
						} );
				}
			} );

		// Empty the view wrap and marker nodes
		function emptyViewNodes( rootNode )
		{
			$( 'div[data-evo-view-text], span[data-evo-view-text], p[data-evo-view-marker]', rootNode ).each( function( i, node ) {
					node.innerHTML = '.';
				} );
		}

		// Run that before the DOM cleanup
		editor.on( 'PreProcess', function( event ) {
				emptyViewNodes( event.node );
			}, true );

		editor.on( 'hide', function() {
				evo.views.unbind();
				deselect();
				emptyViewNodes();
			} );

		editor.on( 'PostProcess', function( event ) {
				if( event.content )
				{
					event.content = event.content.replace( /<(?:div|span) [^>]*?data-evo-view-text="([^"]+)"[^>]*>[\s\S]*?<\/(?:div|span)>/g, resetViewsCallback )
							.replace( /<p [^>]*?data-evo-view-marker="([^"]+)"[^>]*>[\s\S]*?<\/p>/g, resetViewsCallback );
				}
			} );

		// Excludes arrow keys, delete, backspace, enter, space bar.
		// Ref: https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent.keyCode
		function isSpecialKey( key )
		{
			return ( ( key <= 47 && key !== VK.SPACEBAR && key !== VK.ENTER && key !== VK.DELETE && key !== VK.BACKSPACE && ( key < 37 || key > 40 ) ) ||
				key >= 224 || // OEM or non-printable
				( key >= 144 && key <= 150 ) || // Num Lock, Scroll Lock, OEM
				( key >= 91 && key <= 93 ) || // Windows keys
				( key >= 112 && key <= 135 ) ); // F keys
		}

		// (De)select views when arrow keys are used to navigate the content of the editor.
		editor.on( 'keydown', function( event ) {
				var key = event.keyCode,
						dom = editor.dom,
						selection = editor.selection,
						node, view, cursorBefore, cursorAfter,
						range, clonedRange, tempRange;

				if( selected )
				{	// Ignore key presses that involve the command or control key, but continue when in combination with backspace or v.
					// Also ignore the F# keys.
					if( ( /* Ctrl+v */ ( event.metaKey || event.ctrlKey ) && key !== VK.BACKSPACE && key !== 86 ) || ( /*F# keys*/ key >= 112 && key <= 123 ) )
					{	// Remove the view when pressing cmd/ctrl+x on keyup, otherwise the browser can't copy the content.
						if( /* Ctrl+x */ ( event.metaKey || event.ctrlKey ) && key === 88 )
						{
							toRemove = selected;
						}

						return;
					}

					view = getView( selection.getNode() );

					// If the caret is not within the selected view, deselect the view and bail.
					if( view !== selected )
					{
						deselect();
						return;
					}

					if( key === VK.LEFT )
					{
						setViewCursor( true, view );
						event.preventDefault();
					}
					else if( key === VK.UP )
					{
						if( view.previousSibling )
						{
							if( getView( view.previousSibling ) )
							{
								setViewCursor( true, view.previousSibling );
							}
							else
							{
								deselect();
								selection.select( view.previousSibling, true );
								selection.collapse();
							}
						}
						else
						{
							setViewCursor( true, view );
						}
						event.preventDefault();
					}
					else if( key === VK.RIGHT )
					{
						setViewCursor( false, view );
						event.preventDefault();
					}
					else if( key === VK.DOWN )
					{
						if( view.nextSibling )
						{
							if( getView( view.nextSibling ) )
							{
								setViewCursor( false, view.nextSibling );
							}
							else
							{
								deselect();
								selection.setCursorLocation( view.nextSibling, 0 );
							}
						}
						else
						{
							setViewCursor( false, view );
						}

						event.preventDefault();
					// Ignore keys that don't insert anything.
					}
					else if( ! isSpecialKey( key ) )
					{
						if( key === VK.ENTER )
						{
							handleEnter( view, false, key );
							event.preventDefault();
						}
						else if( key === VK.DELETE /*|| key === VK.BACKSPACE*/ )
						{
							removeView( selected );
							deselect();
							event.preventDefault();
						}
						else if( key === VK.BACKSPACE )
						{
							previousView = getView( view.previousElementSibling );

							if( previousView )
							{
								removeView( previousView );
							}
							else if( view.previousSibling )
							{
								selection.setCursorLocation( view.previousSibling );
								deselect();
							}
							else if( view.parentNode.previousSibling )
							{
								selection.setCursorLocation( view.parentNode.previousSibling, 1 );
								deselect();
							}
						}
					}
				}
				else
				{
					if( event.metaKey || event.ctrlKey || ( key >= 112 && key <= 123 ) )
					{
						return;
					}

					node = selection.getNode();
					lastKeyDownNode = node;
					view = getView( node );

					// Make sure we don't delete part of a view.
					// If the range ends or starts with the view, we'll need to trim it.
					if( ! selection.isCollapsed() )
					{
						range = selection.getRng();

						if( view = getView( range.endContainer ) )
						{
							clonedRange = range.cloneRange();
							selection.select( view.previousSibling, true );
							selection.collapse();
							tempRange = selection.getRng();
							clonedRange.setEnd( tempRange.endContainer, tempRange.endOffset );
							selection.setRng( clonedRange );
						}
						else if( view = getView( range.startContainer ) )
						{
							clonedRange = range.cloneRange();
							clonedRange.setStart( view.nextSibling, 0 );
							selection.setRng( clonedRange );
						}
					}

					if( ! view )
					{	// Make sure we don't eat any content.
						if( key === VK.BACKSPACE )
						{
							if( editor.dom.isEmpty( node ) )
							{
								if( view = getView( node.previousSibling ) )
								{
									setViewCursor( false, view );
									editor.dom.remove( node );
									event.preventDefault();
								}
							}
							else if( ( range = selection.getRng() )
									&& range.startOffset === 0
									&& range.endOffset === 0
									&& ( view = getView( node.previousSibling ) ) )
							{
								setViewCursor( false, view );
								event.preventDefault();
							}
						}

						return;
					}

					var cursorBefore = dom.hasClass( view, 'evo-view-selection-before' );
					var cursorAfter  = dom.hasClass( view, 'evo-view-selection-after' );

					if( ! cursorBefore && ! cursorAfter )
					{	// Cursor not before or after a View, exit:
						return;
					}

					if( isSpecialKey( key ) )
					{	// ignore
						return;
					}

					if( ( cursorAfter && key === VK.UP ) || ( cursorBefore && key === VK.BACKSPACE ) )
					{
						if( view.previousSibling )
						{
							if( getView( view.previousSibling ) )
							{
								setViewCursor( false, view.previousSibling );
							}
							else
							{
								if( dom.isEmpty( view.previousSibling ) && key === VK.BACKSPACE )
								{
									dom.remove( view.previousSibling );
								}
								else
								{
									selection.select( view.previousSibling, true );
									selection.collapse();
								}
							}
						}
						else
						{
							setViewCursor( true, view );
						}
						event.preventDefault();
					}
					else if( cursorAfter && ( key === VK.DOWN || key === VK.RIGHT ) )
					{
						if( view.nextSibling )
						{
							if( getView( view.nextSibling ) )
							{
								setViewCursor( key === VK.RIGHT, view.nextSibling );
							}
							else
							{
								selection.setCursorLocation( view.nextSibling );
							}
						}
						else if( view.parentNode.nextSibling )
						{
							if( getView( view.parentNode.nextSibling ) )
							{
								setViewCursor( key === VK.RIGHT, view.parentNode.nextSibling );
							}
							else
							{
								selection.setCursorLocation( view.parentNode.nextSibling );
							}
						}
						event.preventDefault();
					}
					else if( cursorBefore && ( key === VK.UP || key ===  VK.LEFT ) )
					{
						if( view.previousSibling )
						{
							if( getView( view.previousSibling ) )
							{
								setViewCursor( key === VK.UP, view.previousSibling );
							}
							else
							{
								selection.select( view.previousSibling, true );
								selection.collapse();
							}
						}
						else if( view.parentNode.previousSibling )
						{
							if( getView( view.parentNode.previousSibling ) )
							{
								setViewCursor( key === VK.RIGHT, view.parentNode.previousSibling );
							}
							else
							{
								selection.select( view.parentNode.previousSibling, true );
								selection.collapse( false );
							}
						}
						event.preventDefault();
					}
					else if( cursorBefore && key === VK.DOWN )
					{
						if( view.nextSibling )
						{
							if( getView( view.nextSibling ) )
							{
								setViewCursor( true, view.nextSibling );
							}
							else
							{
								selection.setCursorLocation( view.nextSibling, 0 );
							}
						}
						else
						{
							setViewCursor( false, view );
						}
						event.preventDefault();
					}
					else if( ( cursorAfter && key === VK.LEFT ) || ( cursorBefore && key === VK.RIGHT ) )
					{
						select( view );
						event.preventDefault();
					}
					else if( cursorAfter && key === VK.BACKSPACE )
					{
						removeView( view );
						event.preventDefault();
					}
					else if( cursorBefore && key === VK.DELETE )
					{
						var nextView = getView( view.nextSibling );

						removeView( view );

						if( nextView )
						{
							setViewCursor( true, nextView );
						}

						event.preventDefault();
					}
					else if( cursorAfter && key === VK.DELETE )
					{
						var nextView = getView( view.nextSibling );

						if( nextView )
						{
							setViewCursor( true, nextView );
						}

						event.preventDefault();
					}
					else if( cursorAfter )
					{
						handleEnter( view );
					}
					else if( cursorBefore )
					{
						handleEnter( view , true, key );
					}

					if( key === VK.ENTER )
					{
						event.preventDefault();
					}
				}
			} );

		editor.on( 'keyup', function() {
				if( toRemove )
				{
					removeView( toRemove );
					deselect();
					toRemove = false;
				}

				return;
			} );

		editor.on( 'focus', function() {
				var view;

				focus = true;
				editor.dom.addClass( editor.getBody(), 'has-focus' );

				// Edge case: show the fake caret when the editor is focused for the first time
				// and the first element is a view.
				if( firstFocus && ( view = getView( editor.getBody().firstChild ) ) )
				{
					setViewCursor( true, view );
				}

				firstFocus = false;
			} );

		editor.on( 'blur', function() {
				focus = false;
				editor.dom.removeClass( editor.getBody(), 'has-focus' );
			} );

		editor.on( 'NodeChange', function( event ) {
				var dom = editor.dom,
						views = editor.dom.select( '.evo-view-wrap' ),
						className = event.element.className,
						view = getView( event.element ),
						lKDN = lastKeyDownNode;

				lastKeyDownNode = false;

				clearInterval( cursorInterval );

				// This runs a lot and is faster than replacing each class separately
				tinymce.each( views, function ( view ) {
						if( view.className )
						{
							view.className = view.className.replace( / ?\bevo-view-(?:selection-before|selection-after|cursor-hide)\b/g, '' );
						}
					});

				if( focus && view )
				{
					if( ( className === 'evo-view-selection-before' || className === 'evo-view-selection-after' ) && editor.selection.isCollapsed() )
					{
						setViewCursorTries = 0;

						deselect();

						// Make sure the cursor arrived in the right node.
						// This is necessary for Firefox.
						if( lKDN === view.previousSibling )
						{
							setViewCursor( true, view );
							return;
						}
						else if( lKDN === view.nextSibling )
						{
							setViewCursor( false, view );
							return;
						}

						dom.addClass( view, className );

						cursorInterval = setInterval( function() {
								if( dom.hasClass( view, 'evo-view-cursor-hide' ) )
								{
									dom.removeClass( view, 'evo-view-cursor-hide' );
								}
								else
								{
									dom.addClass( view, 'evo-view-cursor-hide' );
								}
							}, 500 );

						// If the cursor lands anywhere else in the view, set the cursor before it.
						// Only try this once to prevent a loop. (You never know.)
					}
					else if ( ! getParent( event.element, 'evo-view-clipboard' ) && ! setViewCursorTries )
					{
						deselect();
						setViewCursorTries++;
						setViewCursor( true, view );
					}
				}
			} );

		editor.on( 'BeforeExecCommand', function() {
				var node = editor.selection.getNode(), view;

				if( node
						&& ( ( execCommandBefore = node.className === 'evo-view-selection-before' ) || node.className === 'evo-view-selection-after' )
						&& ( view = getView( node ) ) )
				{
					handleEnter( view, execCommandBefore );
					execCommandView = view;
				}
			} );

		editor.on( 'ExecCommand', function() {
				var toSelect, node;

				if( selected )
				{
					toSelect = selected;
					deselect();
					select( toSelect );
				}

				if( execCommandView )
				{
					node = execCommandView[ execCommandBefore ? 'previousSibling' : 'nextSibling' ];

					if( ( node && node.nodeName === 'P' ) && editor.dom.isEmpty( node ) )
					{
						editor.dom.remove( node );
						setViewCursor( execCommandBefore, execCommandView );
					}

					execCommandView = false;
				}
			} );

		editor.on( 'ResolveName', function( event ) {
				if( editor.dom.hasClass( event.target, 'evo-view-wrap' ) )
				{
					event.name = editor.dom.getAttrib( event.target, 'data-evo-view-type' ) || 'evo-view';
					event.stopPropagation();
				}
				else if( getView( event.target ) )
				{
					event.preventDefault();
					event.stopPropagation();
				}
			} );

		/**
		 * Add [image:] button
		 */
		editor.addButton( 'evo_image', {
				text: 'inline image',
				icon: false,
				tooltip: evo_js_lang_edit_image,
				onclick: function() {
						switch( editor.getParam( 'target_type' ) )
						{
							case 'Item':
								if( ! editor.getParam( 'target_ID' ) && ! editor.getParam( 'temp_ID' ) )
								{
									alert( evo_js_lang_alert_before_insert_item  );
									return false;
								}
								break;

							case 'Comment':
								if( ! editor.getParam( 'target_ID' ) )
								{
									alert( evo_js_lang_alert_before_insert_comment );
									return false;
								}
								break;

							case 'EmailCampaign':
								if( ! editor.getParam( 'target_ID' ) )
								{
									alert( evo_js_lang_alert_before_insert_emailcampaign );
									return false;
								}
								break;

							case 'Message':
								if( ! editor.getParam( 'target_ID' ) && ! editor.getParam( 'temp_ID' ) )
								{
									alert( evo_js_lang_alert_before_insert_message );
									return false;
								}
								break;
						}

						editView();
					},
				onPostRender: function() {
						var imageButton = this;

						editor.on( 'NodeChange', function( event ) {
								var viewType;

								if( selectedView )
								{
									viewType = selectedView.getAttribute( 'data-evo-view-type' );
								}
								var isImage = shortTags.indexOf( viewType ) != -1;
								imageButton.active( isImage );
							} );
					}
			} );

		editor.on( 'evotoolbar', function( event ) {
				if( selected )
				{
					event.element = selected;
					event.toolbar = toolbar;
				}
			} );

		editor.addCommand( 'evo_view_edit_inline', function() {
				editView();
			} );

		return {
				getMetadata: function () {
						return  {
							name: "b2evo View plugin",
							url: "http://b2evolution.net"
						};
					}
			};

	} );

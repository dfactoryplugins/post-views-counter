// set initial variables
const { __ } = wp.i18n;
const { Fragment, Component } = wp.element;
const { withState, compose } = wp.compose;
const { withSelect, withDispatch } = wp.data;
const { registerPlugin } = wp.plugins;
const { TextControl, Button, Popover } = wp.components;
const { PluginPostStatusInfo } = wp.editPost;

// setup wrapper component
class PostViews extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			postViews: pvcEditorArgs.postViews,
			isVisible: false
		}

		// bind to provide access to 'this' object
        this.handleClick = this.handleClick.bind( this );
        this.handleClickOutside = this.handleClickOutside.bind( this );
		this.handleCancel = this.handleCancel.bind( this );
		this.handleSetViews = this.handleSetViews.bind( this );
	}
	// show/hide popover on button click
    handleClick( e ) {
		if ( e.target.classList.contains( 'edit-post-post-views-toggle-link' ) ) {
			this.setState( ( prevState ) => ( 
				{ isVisible: ! prevState.isVisible }
			) )
		}
	}
	// show/hide popover on outside click
	handleClickOutside( e ) {
		if ( ! e.target.classList.contains( 'edit-post-post-views-toggle-link' ) ) {
			this.setState( ( prevState ) => ( 
				{ isVisible: ! prevState.isVisible }
			) )
		}
	}
	// reset views on cancel click
	handleCancel( e ) {
		this.setState( ( prevState ) => ( 
			{ 
				postViews: pvcEditorArgs.postViews,
				isVisible: ! prevState.isVisible
			}
		) )
	}
	// reset post views on change
	handleSetViews( value ) {
		// force update button to be clickable
		wp.data.dispatch( 'core/editor' ).editPost( { meta: { _pvc_post_views: value } } );

		this.setState( () => {
			return {
				postViews: value
			}
		} );
	}
	// save the post views
	static getDerivedStateFromProps( nextProps, state ) {
		// bail if autosave
		if ( ( nextProps.isPublishing || nextProps.isSaving ) && !nextProps.isAutoSaving ) {
			wp.apiRequest( { path: `/post-views-counter/update-post-views/?id=${nextProps.postId}`, method: 'POST', data: { post_views: state.postViews } } ).then(
				( data ) => {
					console.log( data );
					return data;
				},
				( error ) => {
					console.log( data );
					return error;
				}
			)
		}
	}
	render() {
		return (
			<PostViewsComponent 
				postViews={ this.state.postViews } 
				isVisible={ this.state.isVisible }
				handleClick={ this.handleClick } 
				handleClickOutside={ this.handleClickOutside } 
				handleCancel={ this.handleCancel }
				handleSetViews={ this.handleSetViews } 
			/>
		)
	}
}

// create child component
const PostViewsComponent = ( props ) => {
	// render component
    return (
		<Fragment>
			<PluginPostStatusInfo className="edit-post-post-views"	>
				<span>{ pvcEditorArgs.textPostViews}</span>
				{ ! pvcEditorArgs.canEdit && <span>{ props.postViews }</span> }
				{ pvcEditorArgs.canEdit && (
					<Button 
						isLink
						className="edit-post-post-views-toggle-link"
						onClick={ props.handleClick }
					>
						{ props.postViews }
						{ props.isVisible && (
							<Popover
								position="bottom right"
								className="edit-post-post-views-popover"
								onClickOutside={ props.handleClickOutside }
							>
								<legend>{ pvcEditorArgs.textPostViews }</legend>
								<TextControl
									className="edit-post-post-views-input"
									type={ 'number' }
									key={ 'post_views' }
									value={ props.postViews }
									onChange={ props.handleSetViews }
								/>
								<p className="description">{ pvcEditorArgs.textHelp }</p>
								<Button 
									isLink
									className="edit-post-post-views-cancel-link"
									onClick={ props.handleCancel }
								>
									{ pvcEditorArgs.textCancel }
								</Button>
							</Popover>
						) }
					</Button>
				) }
			</PluginPostStatusInfo>
		</Fragment>
    )
}

// get post data using withSelect higher-order component
const Plugin = withSelect( ( select, { forceIsSaving } ) => {
	const {
		getCurrentPostId,
		isSavingPost,
		isPublishingPost,
		isAutosavingPost,
	} = select( 'core/editor' );
	return {
		postId: getCurrentPostId(),
		isSaving: forceIsSaving || isSavingPost(),
		isAutoSaving: isAutosavingPost(),
		isPublishing: isPublishingPost(),
	};
} )( PostViews )

// register the plugin
registerPlugin( 'post-views-counter', {
	icon: '',
	render: Plugin,
} )
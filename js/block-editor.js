const { Fragment, Component } = wp.element;
const { withSelect } = wp.data;
const { registerPlugin } = wp.plugins;
const { TextControl, Button, Popover } = wp.components;
const { PluginPostStatusInfo } = wp.editPost;
class PostViews extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      postViews: pvcEditorArgs.postViews,
      isVisible: false
    };
    this.handleClick = this.handleClick.bind(this);
    this.handleClickOutside = this.handleClickOutside.bind(this);
    this.handleCancel = this.handleCancel.bind(this);
    this.handleSetViews = this.handleSetViews.bind(this);
  }
  // show/hide popover on button click
  handleClick(e) {
    if (e.target.classList.contains("edit-post-post-views-toggle-link")) {
      this.setState((prevState) => ({ isVisible: !prevState.isVisible }));
    }
  }
  // show/hide popover on outside click
  handleClickOutside(e) {
    if (!e.target.classList.contains("edit-post-post-views-toggle-link")) {
      this.setState((prevState) => ({ isVisible: !prevState.isVisible }));
    }
  }
  // reset views on cancel click
  handleCancel(e) {
    this.setState((prevState) => ({
      postViews: pvcEditorArgs.postViews,
      isVisible: !prevState.isVisible
    }));
  }
  // reset post views on change
  handleSetViews(value) {
    wp.data.dispatch("core/editor").editPost({ meta: { _pvc_post_views: value } });
    this.setState(() => {
      return {
        postViews: value
      };
    });
  }
  // save the post views
  static getDerivedStateFromProps(nextProps, state) {
    if ((nextProps.isPublishing || nextProps.isSaving) && !nextProps.isAutoSaving) {
      wp.apiRequest({ path: `/post-views-counter/update-post-views/?id=${nextProps.postId}`, method: "POST", data: { post_views: state.postViews } }).then(
        (data) => {
          return data;
        },
        (error) => {
          return error;
        }
      );
    }
  }
  render() {
    return /* @__PURE__ */ wp.element.createElement(
      PostViewsComponent,
      {
        postViews: this.state.postViews,
        isVisible: this.state.isVisible,
        handleClick: this.handleClick,
        handleClickOutside: this.handleClickOutside,
        handleCancel: this.handleCancel,
        handleSetViews: this.handleSetViews
      }
    );
  }
}
const PostViewsComponent = (props) => {
  return /* @__PURE__ */ wp.element.createElement(Fragment, null, /* @__PURE__ */ wp.element.createElement(PluginPostStatusInfo, { className: "edit-post-post-views" }, /* @__PURE__ */ wp.element.createElement("div", { className: "editor-post-panel__row-label" }, /* @__PURE__ */ wp.element.createElement("span", null, pvcEditorArgs.textPostViews)), !pvcEditorArgs.canEdit && /* @__PURE__ */ wp.element.createElement("div", { className: "editor-post-panel__row-control" }, /* @__PURE__ */ wp.element.createElement("div", { className: "components-dropdown edit-post-post-views-popover-wrapper" }, /* @__PURE__ */ wp.element.createElement(
    Button,
    {
      size: "compact",
      variant: "tertiary",
      disabled: true,
      className: "components-button edit-post-post-views-toggle-link"
    },
    props.postViews
  ))), pvcEditorArgs.canEdit && /* @__PURE__ */ wp.element.createElement("div", { className: "editor-post-panel__row-control" }, /* @__PURE__ */ wp.element.createElement("div", { className: "components-dropdown edit-post-post-views-popover-wrapper" }, /* @__PURE__ */ wp.element.createElement(
    Button,
    {
      size: "compact",
      variant: "tertiary",
      className: "components-button edit-post-post-views-toggle-link",
      onClick: props.handleClick
    },
    props.postViews,
    props.isVisible && (pvcEditorArgs.wpGreater53 ? /* @__PURE__ */ wp.element.createElement(
      Popover,
      {
        position: "bottom right",
        className: "edit-post-post-views-popover",
        onFocusOutside: props.handleClickOutside
      },
      /* @__PURE__ */ wp.element.createElement("legend", null, pvcEditorArgs.textPostViews),
      /* @__PURE__ */ wp.element.createElement(
        TextControl,
        {
          className: "edit-post-post-views-input",
          type: "number",
          key: "post_views",
          value: props.postViews,
          onChange: props.handleSetViews
        }
      ),
      /* @__PURE__ */ wp.element.createElement("p", { className: "description" }, pvcEditorArgs.textHelp),
      /* @__PURE__ */ wp.element.createElement(
        Button,
        {
          isLink: true,
          className: "edit-post-post-views-cancel-link",
          onClick: props.handleCancel
        },
        pvcEditorArgs.textCancel
      )
    ) : /* @__PURE__ */ wp.element.createElement(
      Popover,
      {
        position: "bottom right",
        className: "edit-post-post-views-popover",
        onClickOutside: props.handleClickOutside
      },
      /* @__PURE__ */ wp.element.createElement("legend", null, pvcEditorArgs.textPostViews),
      /* @__PURE__ */ wp.element.createElement(
        TextControl,
        {
          className: "edit-post-post-views-input",
          type: "number",
          key: "post_views",
          value: props.postViews,
          onChange: props.handleSetViews
        }
      ),
      /* @__PURE__ */ wp.element.createElement("p", { className: "description" }, pvcEditorArgs.textHelp),
      /* @__PURE__ */ wp.element.createElement(
        Button,
        {
          isLink: true,
          className: "edit-post-post-views-cancel-link",
          onClick: props.handleCancel
        },
        pvcEditorArgs.textCancel
      )
    ))
  )))));
};
const Plugin = withSelect((select, { forceIsSaving }) => {
  const {
    getCurrentPostId,
    isSavingPost,
    isPublishingPost,
    isAutosavingPost
  } = select("core/editor");
  return {
    postId: getCurrentPostId(),
    isSaving: forceIsSaving || isSavingPost(),
    isAutoSaving: isAutosavingPost(),
    isPublishing: isPublishingPost()
  };
})(PostViews);
registerPlugin("post-views-counter", {
  icon: "",
  render: Plugin
});

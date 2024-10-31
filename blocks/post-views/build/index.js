(()=>{"use strict";var e={n:o=>{var t=o&&o.__esModule?()=>o.default:()=>o;return e.d(t,{a:t}),t},d:(o,t)=>{for(var r in t)e.o(t,r)&&!e.o(o,r)&&Object.defineProperty(o,r,{enumerable:!0,get:t[r]})},o:(e,o)=>Object.prototype.hasOwnProperty.call(e,o)};const o=window.wp.blocks,t=window.wp.components,r=window.wp.i18n,s=window.wp.blockEditor,n=window.wp.serverSideRender;var i=e.n(n);const p=window.ReactJSXRuntime;(0,o.registerBlockType)("post-views-counter/post-views",{edit:function({attributes:e,setAttributes:o}){const{postID:n,period:c}=e;return(0,p.jsxs)(p.Fragment,{children:[(0,p.jsx)(s.InspectorControls,{children:(0,p.jsxs)(t.PanelBody,{title:(0,r.__)("Settings","post-views-counter"),children:[(0,p.jsx)(t.TextControl,{__nextHasNoMarginBottom:!0,label:(0,r.__)("Post ID","post-views-counter"),value:n,onChange:e=>o({postID:Number(e)}),help:(0,r.__)("Enter 0 to use current visited post.","post-views-counter")}),(0,p.jsx)(t.SelectControl,{__nextHasNoMarginBottom:!0,disabled:1===pvcBlockEditorData.periods.length,label:(0,r.__)("Views period","post-views-counter"),value:c,options:pvcBlockEditorData.periods,onChange:e=>o({period:e})})]})}),(0,p.jsx)("div",{...(0,s.useBlockProps)(),children:(0,p.jsx)(i(),{httpMethod:"POST",block:"post-views-counter/post-views",attributes:e,LoadingResponsePlaceholder:()=>(0,p.jsx)(t.Spinner,{}),ErrorResponsePlaceholder:e=>(0,p.jsx)(t.Notice,{status:"error",children:(0,r.__)("Something went wrong. Try again or refresh the page.","post-views-counter")})})})]})}})})();
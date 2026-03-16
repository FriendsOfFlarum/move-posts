import Extend from 'flarum/common/extenders';
import Discussion from 'flarum/common/models/Discussion';
import PostMovedPost from './components/PostMovedPost';

export default [new Extend.Model(Discussion).attribute<boolean>('isFirstMoved'), new Extend.PostTypes().add('postMoved', PostMovedPost)];

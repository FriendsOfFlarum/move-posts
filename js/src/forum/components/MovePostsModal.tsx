import app from 'flarum/forum/app';
import Button from 'flarum/common/components/Button';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Switch from 'flarum/common/components/Switch';
import DiscussionSearch from 'flarum/ui-kit/forum/DiscussionSearch';
import type Discussion from 'flarum/common/models/Discussion';
import GlobalSearchState from 'flarum/forum/states/GlobalSearchState';
import LinkButton from 'flarum/common/components/LinkButton';

export interface MovePostsModalAttrs extends IInternalModalAttrs {
  discussion: Discussion;
  postIds: string[];
}

export interface MovePostsResponse {
  data: {
    id: string;
    type: 'discussions';
    attributes: any;
  };
  meta: {
    status: string;
    postCount: number;
    firstMovedPostNumber: number;
    sourceDiscussionId: string;
    targetDiscussionId: string;
  };
}

export default class MovePostsModal extends Modal<MovePostsModalAttrs> {
  isLoading: string | boolean = false;
  newDiscussion: boolean = false;
  newDiscussionTitle: string = '';
  targetDiscussionId?: string;
  search = new GlobalSearchState();

  className() {
    return 'MovePostsModal';
  }

  title() {
    return app.translator.trans('fof-move-posts.forum.modal.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <form className="Form" onsubmit={this.onsubmit.bind(this)}>
          <div className="Form-group">
            <label>{app.translator.trans('fof-move-posts.forum.modal.selected_posts', { count: this.attrs.postIds.length })}</label>
            <input className="FormControl" readonly value={this.attrs.postIds.join(', ')} />
          </div>
          <div className="Form-group">
            <Switch state={this.newDiscussion} onchange={() => (this.newDiscussion = !this.newDiscussion)}>
              {app.translator.trans('fof-move-posts.forum.modal.new_discussion')}
            </Switch>
          </div>
          {this.newDiscussion ? (
            <div className="Form-group">
              <label for="discussion_name">{app.translator.trans('fof-move-posts.forum.modal.discussion_name')}</label>
              <p className="helptext">{app.translator.trans('fof-move-posts.forum.modal.discussion_help')}</p>
              <input id="discussion_name" className="FormControl" required={true} oninput={(e: any) => (this.newDiscussionTitle = e.target.value)} />
            </div>
          ) : (
            <div className="Form-group">
              <label for="destination">{app.translator.trans('fof-move-posts.forum.modal.destination')}</label>
              <DiscussionSearch
                state={this.search}
                ignore={this.attrs.discussion.id()}
                onSelect={(discussion: Discussion) => (this.targetDiscussionId = discussion.id())}
              />
            </div>
          )}
          <div className="Form-group Form-controls">
            <Button
              className="Button Button--primary"
              type="submit"
              loading={this.isLoading === 'submit'}
              disabled={this.isLoading === 'check' || (!this.targetDiscussionId && !this.newDiscussionTitle)}
            >
              {app.translator.trans('fof-move-posts.forum.modal.submit')}
            </Button>
            <Button
              className="Button"
              onclick={this.emulate.bind(this)}
              loading={this.isLoading === 'check'}
              disabled={this.isLoading === 'submit' || (!this.targetDiscussionId && !this.newDiscussionTitle)}
            >
              {app.translator.trans('fof-move-posts.forum.modal.check')}
            </Button>
          </div>
        </form>
      </div>
    );
  }

  data() {
    const data: Record<string, unknown> = {
      sourceDiscussionId: this.attrs.discussion.id(),
      postIds: this.attrs.postIds,
    };

    if (this.newDiscussion) {
      data.newDiscussion = true;
      data.newDiscussionTitle = this.newDiscussionTitle;
    } else {
      data.targetDiscussionId = this.targetDiscussionId;
    }

    return data;
  }

  emulate() {
    this.onsubmit(null, true).then((response: any) => {
      switch (response.data.attributes.status) {
        case 'old_to_new_move':
          this.alertAttrs = { type: 'error', content: app.translator.trans('fof-move-posts.forum.modal.status.old_to_new_move') };
          break;

        case 'simple_move':
          this.alertAttrs = { type: 'success', content: app.translator.trans('fof-move-posts.forum.modal.status.simple_move') };
          break;

        case 'complex_move':
          this.alertAttrs = { type: 'warning', content: app.translator.trans('fof-move-posts.forum.modal.status.complex_move') };
          break;

        default:
          break;
      }

      m.redraw();
    });
  }

  override onsubmit(e: SubmitEvent | null, emulate: boolean = false) {
    if (e) e.preventDefault();

    this.isLoading = emulate ? 'check' : 'submit';
    let url = '/api/posts/move';

    if (emulate) url += '/check';

    return app
      .request<MovePostsResponse>({
        method: 'POST',
        url: `${app.forum.attribute('baseUrl')}${url}`,
        body: { data: this.data() },
        errorHandler: (e: any) => {
          const error = e.response.errors[0];
          this.isLoading = false;

          if (!['move_old_post_to_newer_discussion', 'move_posts_to_same_discussion'].includes(error.code)) {
            throw e;
          }

          this.alertAttrs = {
            type: 'error',
            content: app.translator.trans(`fof-move-posts.forum.error.${error.code}`),
          };

          m.redraw();
        },
      })
      .then((response) => {
        this.isLoading = false;
        if (!emulate) {
          app.store.pushPayload<Discussion>(response);

          const targetDiscussion = app.store.getById<Discussion>('discussions', response.data.id);

          app.alerts.show(
            {
              type: 'success',
            },
            targetDiscussion
              ? app.translator.trans('fof-move-posts.forum.alerts.posts_moved_to', {
                  count: response.meta.postCount,
                  target_discussion: (
                    <LinkButton href={app.route.discussion(targetDiscussion, response.meta.firstMovedPostNumber)}>
                      {targetDiscussion.title()}
                    </LinkButton>
                  ),
                })
              : app.translator.trans('fof-move-posts.forum.alerts.posts_moved', { count: response.meta.postCount })
          );

          if (targetDiscussion) {
            m.route.set(app.route.discussion(targetDiscussion, response.meta.firstMovedPostNumber));
          }

          this.hide();
        }

        return response;
      });
  }
}

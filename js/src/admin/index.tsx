import extractText from 'flarum/common/utils/extractText';

app.initializers.add('fof/move-posts', () => {
  let value =
    app.data.settings['fof-move-posts.moved_first_post_content'] ||
    extractText(app.translator.trans('fof-move-posts.lib.discussion.first_post.default_content'));

  app.extensionData
    .for('sycho-move-posts')
    .registerSetting(function () {
      return (
        <div className="Form-group">
          <label for="moved_first_post_content">{app.translator.trans('fof-move-posts.admin.settings.moved_first_post_content')}</label>
          <div className="helpText">{app.translator.trans('fof-move-posts.admin.settings.moved_first_post_content_help')}</div>
          <textarea
            id="moved_first_post_content"
            oninput={(e: any) => {
              value = e.target.value;
              this.setting('fof-move-posts.moved_first_post_content')(e.target.value);
            }}
            className="FormControl"
            required
          >
            {value}
          </textarea>
        </div>
      );
    })
    .registerSetting({
      setting: 'fof-move-posts.group_sequential_event_posts',
      label: app.translator.trans('fof-move-posts.admin.settings.group_sequential_posts'),
      type: 'boolean',
    })
    .registerPermission(
      {
        icon: 'fas fa-exchange-alt',
        label: app.translator.trans('fof-move-posts.admin.permissions.move_posts'),
        permission: 'sycho-move-posts:movePosts',
      },
      'moderate'
    );
});

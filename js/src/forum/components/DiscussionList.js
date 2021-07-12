import Component from '../../common/Component';
import DiscussionListItem from './DiscussionListItem';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Placeholder from '../../common/components/Placeholder';
import Discussion from '../../common/models/Discussion';

/**
 * The `DiscussionList` component displays a list of discussions.
 *
 * ### Attrs
 *
 * - `state` A DiscussionListState object that represents the discussion lists's state.
 */
export default class DiscussionList extends Component {
  view() {
    /**
     * @type DiscussionListState
     */
    const state = this.attrs.state;

    const params = state.getParams();
    let loading;

    if (state.isInitialLoading() || state.isLoadingNext()) {
      loading = <LoadingIndicator />;
    } else if (state.hasNext()) {
      loading = Button.component(
        {
          className: 'Button',
          onclick: state.loadNext.bind(state),
        },
        app.translator.trans('core.forum.discussion_list.load_more_button')
      );
    }

    if (state.isEmpty()) {
      const text = app.translator.trans('core.forum.discussion_list.empty_text');
      return <div className="DiscussionList">{Placeholder.component({ text })}</div>;
    }

    return (
      <div className={'DiscussionList' + (state.isSearchResults() ? ' DiscussionList--searchResults' : '')}>
        {state.hasPrev() && (
          <div className="DiscussionList-loadPrev">
            {state.isLoadingPrev() ? (
              <LoadingIndicator />
            ) : (
              <Button className="Button" onclick={state.loadPrev.bind(state)}>
                {app.translator.trans('core.forum.discussion_list.load_prev_button')}
              </Button>
            )}
          </div>
        )}

        <ul className="DiscussionList-discussions">
          {state.getPages().map((pg) => {
            return [
              <hr key={`page-${pg.number}`} data-page={pg.number} />,
              ...pg.items.map((discussion) => (
                <li key={discussion.id()} data-id={discussion.id()}>
                  {DiscussionListItem.component({ discussion, params })}
                </li>
              )),
            ];
          })}
        </ul>
        <div className="DiscussionList-loadMore">{loading}</div>
      </div>
    );
  }
}

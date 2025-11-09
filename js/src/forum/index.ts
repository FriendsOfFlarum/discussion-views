import app from 'flarum/forum/app';
import AddSort from './components/AddSort';
import AddViewsToModelAndDisplay from './components/AddViewsToModelAndDisplay';
import AddModerationControl from './components/AddModerationControl';

export { default as extend } from './extend';

app.initializers.add('fof-discussion-views', () => {
  AddSort();
  AddViewsToModelAndDisplay();
  AddModerationControl();
});

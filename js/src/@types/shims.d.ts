import Model from 'flarum/common/Model';

declare module 'flarum/common/models/Discussion' {
  export default interface Discussion {
    canReset(): boolean;
    views(): number;
  }
}

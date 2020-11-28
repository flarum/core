import subclassOf from "../../../../src/common/utils/subclassOf";

class Parent { }

class Child extends Parent { }

test('works as expected with basic example', () => {
  expect(subclassOf(Child, Parent)).toBe(true);
});

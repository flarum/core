import abbreviateNumber from "../../../../src/common/utils/abbreviateNumber";


test('does not change small numbers', () => {
  console.log(abbreviateNumber)
  expect(abbreviateNumber.default(1)).toBe("1");
});
